<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * The Control Panel controller class
 *
 */
class ArsControllerCpanel extends FOFController
{
	public function execute($task)
	{
		if (!in_array($task, array('updategeoip')))
		{
			$task = 'browse';
		}

		$this->task = 'browse';

		parent::execute($task);
	}

	public function updategeoip()
	{
		$geoip = new AkeebaGeoipProvider();
		$result = $geoip->updateDatabase();

		$url = 'index.php?option=com_admintools';

		if ($result === true)
		{
			$msg = JText::_('COM_ARS_GEOBLOCK_MSG_DOWNLOADEDGEOIPDATABASE');
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $result, 'error');
		}
	}
}