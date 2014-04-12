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
		if (!in_array($task, array('updategeoip', 'updateinfo')))
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

		// Run the automatic update site refresh
		/** @var ArsModelUpdates $updateModel */
		$updateModel = F0FModel::getTmpInstance('Updates', 'ArsModel');
		$updateModel->refreshUpdateSite();

		return parent::onBeforeBrowse();
	}

	public function updateinfo()
	{
		/** @var ArsModelUpdates $updateModel */
		$updateModel = F0FModel::getTmpInstance('Updates', 'ArsModel');
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

		echo $result;

		// Cut the execution short
		JFactory::getApplication()->close();
	}
}