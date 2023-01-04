<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class CategoryController extends FormController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_CATEGORY';

	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model = $this->getModel('Category', '', []);

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_ars&view=categories' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$categoryId = (int) isset($data[$key]) ? $data[$key] : 0;

		if (!$categoryId)
		{
			return false;
		}

		return $this->app->getIdentity()->authorise('core.edit', $this->option . '.category.' . $categoryId);
	}
}