<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerCopyTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Joomla\CMS\MVC\Controller\AdminController;

class ReleasesController extends AdminController
{
	use ControllerEvents;
	use ControllerCopyTrait;

	protected $text_prefix = 'COM_ARS_RELEASES';

	public function getModel($name = 'Release', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}