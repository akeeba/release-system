<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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
	protected function onAdd($tpl)
	{
		return $this->onDisplay($tpl);
	}
	
	protected function onDisplay($tpl)
	{
		// Load the model
		$model = $this->getModel();

		// -- Icon definitions
		$this->assign('icondefs',			$model->getIconDefinitions() );
		
		require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/cache.php');
		$cache = new ArsHelperCache();
		
		$popularweek = $cache->getValue('popularweek');
		if(empty($popularweek)) {
			$popularweek = json_encode($model->getWeekPopular());
			$cache->setValue('popularweek', $popularweek);
		}
		$this->assign('popularweek',		json_decode($popularweek) );
		
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
		
		$this->assign('dlmonth',			$dldetails['dlmonth'] );
		$this->assign('dlweek',				$dldetails['dlweek'] );
		$this->assign('dlever',				$dldetails['dlever'] );

		// -- Monthly-Daily downloads report
		$mdreport = $cache->getValue('mdreport');
		if(empty($mdreport)) {
			$mdreport = json_encode($model->getMonthlyStats());
			$cache->setvalue('mdreport', $mdreport);
		}
		$this->assign('mdreport',			json_decode($mdreport, true));
		
		$cache->save();

		return true;
	}
}