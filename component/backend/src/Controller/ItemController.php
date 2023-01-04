<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Model\ItemsModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class ItemController extends FormController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_ITEM';

	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model = $this->getModel('Item', '', []);

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_ars&view=items' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	protected function allowAdd($data = [])
	{
		/**
		 * This method is called twice. Once from the add task with an empty $data array. A second time from the edit
		 * page's save task with the $data to be saved.
		 */
		/** @var ItemsModel $itemsModel */
		$itemsModel = $this->getModel('Items', 'Administrator');
		$releaseId  = $data['release_id'] ?? null;
		$categoryId = $releaseId ? $itemsModel->getCategoryFromRelease($releaseId) : null;

		$user = Factory::getApplication()->getIdentity();

		// This is a pre-add check
		if (empty($data))
		{
			/** @var CMSApplication $app */
			$app            = Factory::getApplication();
			$filterRelease  = (int) $app->getUserState('com_ars.items.filter.category_id', 0);
			$filterCategory = $itemsModel->getCategoryFromRelease($filterRelease);

			$catPermission = ($filterRelease > 0) ? $user->authorise('core.create', 'com_ars.category.' . $filterCategory) : false;

			return $catPermission || $user->authorise('core.create', 'com_ars');
		}

		// This is a save check. Only check the category permissions.
		if (empty($categoryId))
		{
			// When saving an item we MUST have a valid release (which belongs to a valid category)!
			return false;
		}

		return $user->authorise('core.create', 'com_ars.category.' . $categoryId);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId = (int) $data[$key] ?? 0;

		if ($recordId)
		{
			/** @var ItemsModel $itemsModel */
			$itemsModel = $this->getModel('Items', 'Administrator');
			$releaseId  = (int) $this->getModel()->getItem($recordId)->release_id ?: null;
			$categoryId = $releaseId ? $itemsModel->getCategoryFromRelease($releaseId) : null;
		}

		// An item must always belong to a release which must always belong to a category
		if (!$categoryId)
		{
			return false;
		}

		// The category has been set. Check the category permissions.
		return $this->app->getIdentity()->authorise('core.edit', $this->option . '.category.' . $categoryId);
	}
}