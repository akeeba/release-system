<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;


defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Table\LogTable;
use Akeeba\Component\ARS\Site\Mixin\ControllerCRIAccessTrait;
use Akeeba\Component\ARS\Site\Model\ItemModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;
use RuntimeException;

class ItemController extends BaseController
{
	use ControllerEvents;
	use ControllerCRIAccessTrait;
	use TableAssertionTrait;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerDefaultTask('download');
	}


	/**
	 * Downloads an item to the user's browser
	 *
	 * @throws Exception
	 */
	public function download(): void
	{
		$id = $this->input->getInt('item_id', null);

		// Get the page parameters
		/** @var ItemModel $model */
		$model = $this->getModel() ?: $this->getModel('Item', 'Site');

		// Log in a user if I have to
		$model->loginUser();

		try
		{
			$item = $this->accessControlItem($id, false);

			$this->assertNotEmpty($item, 'Item not found or access denied');

			$release = $this->accessControlRelease($item->release_id, false);

			$this->assertNotEmpty($release, 'Release not found or access denied');

			$category = $this->accessControlCategory($release->category_id, false);

			$this->assertNotEmpty($category, 'Category not found or access denied');

			// Make sure this is a valid download item (link or pointing to an existing file)
			$model->preDownloadCheck($item, $category);

			// Hit the item
			$item->save([
				'hits' => ++$item->hits,
			]);

			// Log the download
			/** @var LogTable $log */
			$log = $model->getTable('Log');

			$log->save([
					'item_id'    => $id,
					'authorized' => 1,
				]
			);

			// Download the item
			$model->doDownload($item, $category);

			$this->app->close();
		}
		catch (Exception $e)
		{
			$effectiveId = isset($item) ? ($item->id ?? 0) : 0;
			$effectiveId = $effectiveId ?: $id;
			$this->logFailedDownloadAttempt($effectiveId);

			if (empty($this->redirect))
			{
				$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

				if ($effectiveId && $item->redirect_unauth && $item->show_unauth_links)
				{
					$noAccessURL = $item->redirect_unauth;
				}

				// Do I need to route the redirection URL?
				if ((substr($noAccessURL, 0, 7) !== 'http://') && (substr($noAccessURL, 0, 7) !== 'https://'))
				{
					$noAccessURL = Route::_($noAccessURL);
				}

				if (!empty($noAccessURL))
				{
					$this->setRedirect($noAccessURL);
				}
			}

			if (!empty($this->redirect))
			{
				$model->logoutUser();

				$this->setRedirect($noAccessURL);

				return;
			}

			$model->logoutUser();

			throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403, $e);
		}
	}

	/**
	 * Log a failed download attempt for the given item ID
	 *
	 * @param   int  $id
	 *
	 * @throws Exception
	 */
	private function logFailedDownloadAttempt(int $id): void
	{
		$model = $this->getModel();
		$log   = $model->getTable('Log');

		$log->save([
				'item_id'    => $id,
				'authorized' => 0,
			]
		);

		$params = ComponentHelper::getParams('com_ars');

		if (!$params->get('banUnauth', 0))
		{
			return;
		}

		$extraMessage = $id ? ('Item : ' . $id) : '';

		// Let's fire the system plugin event. If Admin Tools is installed, it will handle this and ban the user
		$this->app->triggerEvent('onAdminToolsThirdpartyException', [
				'external',
				Text::_('COM_ARS_BLOCKED_MESSAGE'),
				[$extraMessage],
				true,
			]
		);
	}

}