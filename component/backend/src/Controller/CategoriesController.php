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
use Joomla\CMS\MVC\Controller\AdminController;

class CategoriesController extends AdminController
{
	use ControllerEvents;
	use ControllerCopyTrait;

	protected $text_prefix = 'COM_ARS_CATEGORIES';

	public function getModel($name = 'Category', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}