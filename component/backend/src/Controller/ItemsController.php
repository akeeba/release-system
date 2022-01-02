<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\Mixin\CopyAware;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Joomla\CMS\MVC\Controller\AdminController;

class ItemsController extends AdminController
{
	use ControllerEvents;
	use CopyAware;

	protected $text_prefix = 'COM_ARS_ITEMS';

	public function getModel($name = 'Item', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}