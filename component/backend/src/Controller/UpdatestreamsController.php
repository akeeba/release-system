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

class UpdatestreamsController extends AdminController
{
	use ControllerEvents;
	use CopyAware;

	protected $text_prefix = 'COM_ARS_UPDATESTREAMS';

	public function getModel($name = 'Updatestream', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}