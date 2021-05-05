<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class ReleaseController extends FormController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_RELEASE';

	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model = $this->getModel('Release', '', []);

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_ars&view=releases' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	protected function allowAdd($data = [])
	{
		$categoryId = $data['category_id'] ?? null;

		if (empty($categoryId))
		{
			return false;
		}

		return Factory::getApplication()->getIdentity()->authorise('core.create', 'com_ars.category.' . $categoryId);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId   = (int) $data[$key] ?? 0;
		$categoryId = 0;

		if ($recordId)
		{
			$categoryId = (int) $this->getModel()->getItem($recordId)->category_id;
		}

		// A release must always belong to a category
		if (!$categoryId)
		{
			return false;
		}

		// The category has been set. Check the category permissions.
		return $this->app->getIdentity()->authorise('core.edit', $this->option . '.category.' . $categoryId);
	}
}