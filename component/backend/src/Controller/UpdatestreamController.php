<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
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

class UpdatestreamController extends FormController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_UPDATESTREAM';

	protected function allowAdd($data = [])
	{
		/**
		 * This method is called twice. Once from the add task with an empty $data array. A second time from the
		 * edit page's save task with the $data to be saved. See ReleaseController::allowAdd() for the full
		 * rationale — an Update Stream always belongs to a category, so category-scoped ACL must be checked here,
		 * not just at the component root.
		 */
		$categoryId = $data['category'] ?? null;
		$user       = Factory::getApplication()->getIdentity();

		// This is a pre-add check
		if (empty($data))
		{
			/** @var CMSApplication $app */
			$app            = Factory::getApplication();
			$filterCategory = (int) $app->getUserState('com_ars.updatestreams.filter.category_id', 0);

			$catPermission = ($filterCategory > 0) ? $user->authorise('core.create', 'com_ars.category.' . $filterCategory) : false;

			return $catPermission || $user->authorise('core.create', 'com_ars');
		}

		// This is a save check. Only check the category permissions.
		if (empty($categoryId))
		{
			// When saving an update stream we MUST have a category!
			return false;
		}

		return $user->authorise('core.create', 'com_ars.category.' . $categoryId);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId   = (int) ($data[$key] ?? 0);
		$categoryId = 0;

		if ($recordId)
		{
			$categoryId = (int) $this->getModel()->getItem($recordId)->category;
		}

		// An update stream must always belong to a category
		if (!$categoryId)
		{
			return false;
		}

		// The category has been set. Check the category permissions.
		return $this->app->getIdentity()->authorise('core.edit', $this->option . '.category.' . $categoryId);
	}
}