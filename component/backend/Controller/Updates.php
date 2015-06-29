<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use FOF30\Container\Container;
use FOF30\Controller\Controller;

class Updates extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['force'];
	}

	public function force()
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Updates $updateModel */
		$updateModel = $this->getModel();

		$updateModel->getUpdates(true);

		$url = 'index.php?option=' . $this->container->componentName;
		$msg = \JText::_('AKEEBA_COMMON_UPDATE_INFORMATION_RELOADED');
		$this->setRedirect($url, $msg);
	}

}