<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\RegisterControllerTasks;
use Akeeba\Component\ARS\Administrator\Mixin\ReusableModels;
use Akeeba\Component\ARS\Administrator\Model\ControlpanelModel;
use Akeeba\Component\ARS\Administrator\Model\UpgradeModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class ControlpanelController extends BaseController
{
	use ReusableModels;
	use ControllerEvents;
	use RegisterControllerTasks;

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main()
	{
		/** @var ControlpanelModel $model */
		$model = $this->getModel();

		$model->saveMagicVariables();

		// Make sure all of my extensions are assigned to my package.
		/** @var UpgradeModel $upgradeModel */
		$upgradeModel = $this->getModel('Upgrade', 'Administrator');
		$upgradeModel->adoptMyExtensions();

		$this->setRedirect('index.php?option=com_cpanel&view=cpanel&dashboard=com_ars.ars');
	}
}