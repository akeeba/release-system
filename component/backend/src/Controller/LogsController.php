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
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class LogsController extends AdminController
{
	use ControllerEvents;
	use ControllerCopyTrait;

	protected $text_prefix = 'COM_ARS_LOGS';

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->unregisterTask('publish');
		$this->unregisterTask('unpublish');
		$this->unregisterTask('archive');
		$this->unregisterTask('trash');
		$this->unregisterTask('report');
		$this->unregisterTask('orderup');
		$this->unregisterTask('orderdown');
		$this->unregisterTask('orderdown');
		$this->unregisterTask('reorder');
		$this->unregisterTask('saveorder');
		$this->unregisterTask('checkin');
		$this->unregisterTask('saveOrderAjax');
		$this->unregisterTask('runTransition');
	}

	public function getModel($name = 'Log', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

}