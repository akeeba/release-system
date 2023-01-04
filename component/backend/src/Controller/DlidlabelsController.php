<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerCopyTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerReturnURLTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class DlidlabelsController extends AdminController
{
	use ControllerEvents;
	use ControllerCopyTrait;
	use ControllerReturnURLTrait;
	use ControllerReusableModelsTrait;

	protected $text_prefix = 'COM_ARS_DLIDLABELS';

	public function getModel($name = 'Dlidlabel', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Resets the Download IDs of one or more records.
	 */
	public function reset()
	{
		$app = $this->app;

		// Check for request forgeries
		$this->checkToken($app->isClient('site') ? 'get' : 'post');

		// Get items to remove from the request.
		$cid = $this->input->get('cid', [], 'array');

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(), false
			)
		);
		$this->applyReturnUrl();

		if (!\is_array($cid) || \count($cid) < 1)
		{
			$this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), ['category' => 'jerror']);

			return;
		}

		// Get the model.
		/** @var DlidlabelModel $model */
		$model = $this->getModel();

		// Make sure the item ids are integers
		$cid = ArrayHelper::toInteger($cid);

		// Reset the items.
		if ($model->reset($cid))
		{
			$this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_RESET', \count($cid)));

			return;
		}

		$this->setMessage($model->getError(), 'error');
	}

}