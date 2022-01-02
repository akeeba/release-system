<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\BleedingEdge;
use Akeeba\ReleaseSystem\Site\Model\Download;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Logs;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF40\Controller\DataController;
use FOF40\Controller\Exception\ItemNotFound;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class Item extends DataController
{
	public function execute($task): ?bool
	{
		// If we're using the JSON API we need a manager
		$format = $this->input->getCmd('format', 'html');

		if (($format == 'json') && !($this->checkACL('core.manage') || $this->checkACL('core.admin')))
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// For the HTML view we only allow browse and download
		if ($format != 'json')
		{
			if (!in_array($task, ['browse', 'download']))
			{
				$task = 'browse';
			}
		}

		return parent::execute($task);
	}

	/**
	 * Overrides the default display method to add caching support
	 *
	 * @param   bool         $cachable   Is this a cacheable view?
	 * @param   bool|array   $urlparams  Registered URL parameters
	 * @param   null|string  $tpl        Sub-template (not really used...)
	 */
	public function display(bool $cachable = false, ?array $urlparams = null, ?string $tpl = null): void
	{
		$cachable = true;

		if (!is_array($urlparams))
		{
			$urlparams = [];
		}

		$additionalParams = [
			'option'     => 'CMD',
			'view'       => 'CMD',
			'task'       => 'CMD',
			'format'     => 'CMD',
			'layout'     => 'CMD',
			'release_id' => 'INT',
			'id'         => 'INT',
			'dlid'       => 'STRING',
		];

		$urlparams = array_merge($additionalParams, $urlparams);

		// Do not cache filterable views
		$layout = $this->input->getCmd('layout', 'default');
		$tmpl   = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$cachable = false;
		}

		parent::display($cachable, $urlparams, $tpl);
	}

	public function onBeforeBrowse(): void
	{
		// Only apply on HTML views
		if (!in_array($this->input->getCmd('format', 'html'), ['html', 'feed']))
		{
			return;
		}

		$layout = $this->input->getCmd('layout', 'default');
		$tmpl   = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$this->onBeforeBrowseModal();

			return;
		}

		// Get the page parameters
		/** @var \JApplicationSite $app */
		$app    = Factory::getApplication();
		$params = $app->getParams('com_ars');

		// Push the page params to the Items model
		/** @var Releases $releaseModel */
		$releaseModel = $this->getModel('Releases');
		$releaseModel
			->orderby_filter($params->get('rel_orderby', 'order'))
			->category_id(0)
			->access_user($this->container->platform->getUser()->id);

		/** @var Items $itemsModel */
		$itemsModel = $this->getModel();
		$itemsModel
			->orderby_filter($params->get('items_orderby', 'order'))
			->release_id(0)
			->access_user($this->container->platform->getUser()->id);

		// Get the release ID
		$id = $this->input->getInt('release_id', 0);

		if (empty($id))
		{
			$id = $params->get('relid', 0);
		}

		// Required for caching
		$this->input->set('relid', null);
		$this->input->set('release_id', $id);

		try
		{
			// Try to find the release
			$releaseModel->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($releaseModel, false) || !$releaseModel->published)
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($releaseModel->id && $releaseModel->redirect_unauth && $releaseModel->show_unauth_links)
			{
				$noAccessURL = $releaseModel->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		try
		{
			// Try to filter the category as well
			$category = $releaseModel->category;

			if (!Filter::filterItem($category, false) || !$category->published)
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($releaseModel->category->id && $releaseModel->category->redirect_unauth && $releaseModel->category->show_unauth_links)
			{
				$noAccessURL = $releaseModel->category->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Filter the releases by this category
		$itemsModel->release($releaseModel->id)->published(1);

		/** @var BleedingEdge $bleedingEdgeModel */
		$bleedingEdgeModel = $this->container->factory->model('BleedingEdge');

		// TODO Check BleedingEdge limits and make sure the release is still published after that

		$bleedingEdgeModel->checkFiles($releaseModel);

		// Push the models to the view
		$this->getView()->setDefaultModel($itemsModel);
		$this->getView()->setModel('Releases', $releaseModel);
	}

	public function onBeforeBrowseModal(): void
	{
		// Intentionally left blank to prevent onBeforeBrowse from kicking in
	}

	/**
	 * Handles input data transformations before telling the Model to save them.
	 *
	 * @param array $data
	 *
	 * @return  void  The $data array is directly handled
	 */
	protected function onBeforeApplySave(array &$data): void
	{
		// If "environments" is a comma separated list of IDs convert to a proper array
		if (isset($data['environments']) && !is_array($data['environments']))
		{
			if (empty($data['environments']))
			{
				$data['environments'] = [];
			}

			if (!is_array($data['environments']))
			{
				$data['environments'] = explode(',', $data['environments']);
				$data['environments'] = array_map(function ($x) {
					return trim($x);
				}, $data['environments']);
			}
		}
	}

	/**
	 * Downloads an item to the user's browser
	 *
	 * @throws \Exception
	 */
	public function download(): void
	{
		$id = $this->input->getInt('id', null);

		// Get the page parameters
		$app    = Factory::getApplication();
		$params = ComponentHelper::getParams('com_ars');

		/** @var Download $model */
		$model = $this->getModel('Download');

		// Log in a user if I have to
		$model->loginUser();

		// Get the log table
		/** @var Logs $log */
		$log = $this->getModel('Logs');

		/** @var Items $item */
		$item = $this->getModel();

		// The item must exist and be accessible
		try
		{
			// Try to find the item
			$item->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($item, false))
			{
				throw new \Exception('Filtering failed');
			}

			if (!$item->published)
			{
				throw new \Exception('Item unpublished');
			}

			if (!$item->release->published)
			{
				throw new \Exception('Release unpublished');
			}

			if (!$item->release->category->published)
			{
				throw new \Exception('Category unpublished');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($item->id ? $id : 0);

			$model->logoutUser();

			$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($item->id && $item->redirect_unauth && $item->show_unauth_links)
			{
				$noAccessURL = $item->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}


		try
		{
			// The release must be accessible
			$release = $item->release;

			// Make sure subscription level filtering allows access
			if (!$release->id || !Filter::filterItem($release, false))
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($id);

			$model->logoutUser();

			$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if (isset($release) && $release->id && $release->redirect_unauth && $release->show_unauth_links)
			{
				$noAccessURL = $release->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// The category must be accessible
		try
		{
			$category = $release->category;

			// Make sure subscription level filtering allows access
			if (!$category->id || !Filter::filterItem($category, false))
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($id);

			$model->logoutUser();

			$noAccessURL = ComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if (isset($category) && $category->id && $category->redirect_unauth && $category->show_unauth_links)
			{
				$noAccessURL = $category->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Hit the item
		$item->save([
			'hits' => ++$item->hits,
		]);

		// Log the download
		$log->create([
				'item_id'    => $id,
				'authorized' => 1,
			]
		);

		// Download the item
		$model->doDownload($item);

		$this->container->platform->closeApplication();
	}

	/**
	 * Log a failed download attempt for the given item ID
	 *
	 * @param int $id
	 *
	 * @throws \Exception
	 */
	private function logFailedDownloadAttempt(int $id): void
	{
		$log = $this->getModel('Logs');

		$log->create([
				'item_id'    => $id,
				'authorized' => 0,
			]
		);

		/** @var \JApplicationSite $app */
		$app    = Factory::getApplication();
		$params = ComponentHelper::getParams('com_ars');

		if ($params->get('banUnauth', 0))
		{
			$extraMessage = $id ? 'Item : ' . $id : '';

			// Let's fire the system plugin event. If Admin Tools is installed, it will handle this and ban the user
			$app->triggerEvent('onAdminToolsThirdpartyException', [
					'external',
					Text::_('COM_ARS_BLOCKED_MESSAGE'),
					[$extraMessage],
					true,
				]
			);
		}
	}
}
