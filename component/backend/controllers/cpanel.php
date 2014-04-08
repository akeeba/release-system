<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * The Control Panel controller class
 *
 */
class ArsControllerCpanel extends F0FController
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
		if ($this->csrfProtection)
		{
			$this->_csrfProtection();
		}

		$geoip = new AkeebaGeoipProvider();
		$result = $geoip->updateDatabase();

		$url = 'index.php?option=com_ars';

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

	protected function onBeforeBrowse()
	{
		/** @var ArsModelCpanels $model */
		$model = $this->getThisModel();
		// Update the database schema if necessary
		$model->checkAndFixDatabase();
		// Refresh the update site
		$model->refreshUpdateSite();

		return parent::onBeforeBrowse();
	}
}