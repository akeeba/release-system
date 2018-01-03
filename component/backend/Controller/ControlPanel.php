<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

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

		$this->predefinedTaskList = ['main', 'updategeoip'];
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
