<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Akeeba Release System Control Panel view class
 *
 */
class ArsViewCpanels extends FOFViewHtml
{
	protected function onAdd($tpl = null)
	{
		return $this->onDisplay($tpl);
	}
	
	protected function onDisplay($tpl = null)
	{
		// Load the model
		$model = $this->getModel();

		// -- Icon definitions
		$this->icondefs = $model->getIconDefinitions();
		
		require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/cache.php');
		$cache = new ArsHelperCache();
		
		$popularweek = $cache->getValue('popularweek');
		if(empty($popularweek)) {
			$popularweek = json_encode($model->getWeekPopular());
			$cache->setValue('popularweek', $popularweek);
		}
		$this->popularweek = json_decode($popularweek);
		
		// -- # of downloads
		$dldetails = $cache->getValue('dldetails');
		if(empty($dldetails)) {
			$dldetails = array();
			$dldetails['dlmonth']		= $model->getNumDownloads('month');
			$dldetails['dlweek']		= $model->getNumDownloads('week');
			$dldetails['dlever']		= $model->getNumDownloads('alltime');
			
			$dldetails = json_encode($dldetails);
			$cache->setValue('dldetails', $dldetails);
		}
		$dldetails = json_decode($dldetails, true);
		
		$this->dlmonth = $dldetails['dlmonth'];
		$this->dlweek = $dldetails['dlweek'];
		$this->dlever = $dldetails['dlever'];

		// -- Monthly-Daily downloads report
		$mdreport = $cache->getValue('mdreport');
		if(empty($mdreport)) {
			$mdreport = json_encode($model->getMonthlyStats());
			$cache->setvalue('mdreport', $mdreport);
		}
		$this->mdreport = json_decode($mdreport, true);
		
		$cache->save();
		
		// Get chart area width
		JLoader::import('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_ars');
		$width = $params->get('graphswidth', 8);
		if($width < 2) {
			$width = 2;
		} elseif($width > 10) {
			$width = 10;
		}
		$this->graphswidth = $width;

		$this->hasplugin = 				$model->hasGeoIPPlugin();
		$this->pluginNeedsUpdate =		$model->dbNeedsUpdate();

		return true;
	}
}