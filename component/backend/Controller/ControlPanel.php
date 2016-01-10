<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Updates;
use AkeebaGeoipProvider;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use JText;

class ControlPanel extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['main', 'updategeoip', 'updateinfo'];
	}

	/**
	 * Runs before the main task, used to perform housekeeping function automatically
	 */
	protected function onBeforeMain()
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model
			->checkAndFixDatabase()
			->saveMagicVariables();

		/** @var \Akeeba\ReleaseSystem\Admin\Model\Updates $updatesModel */
		$updatesModel = $this->getModel('Updates');
		$updatesModel->refreshUpdateSite();
	}

	/**
	 * Force reload the update information
	 */
	public function updateinfo()
	{
		/** @var Updates $updateModel */
		$updateModel = $this->getModel('Updates');
		$updateInfo = (object)$updateModel->getUpdates();

		$result = '';

		if ($updateInfo->hasUpdate)
		{
			$strings = array(
				'header'		=> JText::sprintf('COM_ARS_CPANEL_MSG_UPDATEFOUND', $updateInfo->version),
				'button'		=> JText::sprintf('COM_ARS_CPANEL_MSG_UPDATENOW', $updateInfo->version),
				'infourl'		=> $updateInfo->infoURL,
				'infolbl'		=> JText::_('COM_ARS_CPANEL_MSG_MOREINFO'),
			);

			$result = <<<ENDRESULT
	<div class="alert alert-warning">
		<h3>
			<span class="icon icon-exclamation-sign glyphicon glyphicon-exclamation-sign"></span>
			{$strings['header']}
		</h3>
		<p>
			<a href="index.php?option=com_installer&view=update" class="btn btn-primary">
				{$strings['button']}
			</a>
			<a href="{$strings['infourl']}" target="_blank" class="btn btn-small btn-info">
				{$strings['infolbl']}
			</a>
		</p>
	</div>
ENDRESULT;
		}

		echo '###' . $result . '###';

		// Cut the execution short
		$this->container->platform->closeApplication();
	}

	/**
	 * Update the GeoIP database
	 */
	public function updategeoip()
	{
		if ($this->csrfProtection)
		{
			$this->csrfProtection();
		}

		// Load the GeoIP library if it's not already loaded
		if (!class_exists('AkeebaGeoipProvider'))
		{
			if (@file_exists(JPATH_PLUGINS . '/system/akgeoip/lib/akgeoip.php'))
			{
				if (@include_once JPATH_PLUGINS . '/system/akgeoip/lib/vendor/autoload.php')
				{
					@include_once JPATH_PLUGINS . '/system/akgeoip/lib/akgeoip.php';
				}
			}
		}

		$geoip = new AkeebaGeoipProvider();
		$result = $geoip->updateDatabase();

		$url = 'index.php?option=com_ars';

		if ($result === true)
		{
			$msg = JText::_('COM_ARS_GEOIP_MSG_DOWNLOADEDGEOIPDATABASE');
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $result, 'error');
		}
	}
}