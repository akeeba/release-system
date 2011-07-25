<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Load framework base classes
jimport('joomla.application.component.view');

/**
 * Akeeba Release System Control Panel view class
 *
 */
class ArsViewCpanel extends JView
{
	function display()
	{
		// Set the toolbar title
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD'),'ars');
		JToolBarHelper::preferences('com_ars', '550');

		// Add submenus (those nifty text links below the toolbar!)
		// -- Categories
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=categories';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_CATEGORIES'), $link);
		// -- Releases
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=releases';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_RELEASES'), $link);
		// -- Items
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=items';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_ITEMS'), $link);

		// Load the model
		$model =& $this->getModel();

		// -- Icon definitions
		$this->assign('icondefs',			$model->getIconDefinitions() );
		
		require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'cache.php');
		$cache = new ArsHelperCache();
		
		// -- Popular items (ever & week)
		/*
		$popularever = $cache->getValue('popularever');
		if(empty($popularever)) {
			$popularever = json_encode($model->getAllTimePopular());
			$cache->setValue('popularever', $popularever);
		}
		$this->assign('popularever',		json_decode($popularever) );
		*/
		
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
			//$dldetails['dllastmonth']	= $model->getNumDownloads('lastmonth');
			$dldetails['dlmonth']		= $model->getNumDownloads('month');
			$dldetails['dlweek']		= $model->getNumDownloads('week');
			//$dldetails['dlyear']		= $model->getNumDownloads('year');
			$dldetails['dlever']		= $model->getNumDownloads('alltime');
			
			$dldetails = json_encode($dldetails);
			$cache->setValue('dldetails', $dldetails);
		}
		$dldetails = json_decode($dldetails, true);
		
		//$this->assign('dllastmonth',		$dldetails['dllastmonth'] );
		$this->assign('dlmonth',			$dldetails['dlmonth'] );
		$this->assign('dlweek',				$dldetails['dlweek'] );
		//$this->assign('dlyear',				$dldetails['dlyear'] );
		$this->assign('dlever',				$dldetails['dlever'] );

		// -- Monthly-Daily downloads report
		$mdreport = $cache->getValue('mdreport');
		if(empty($mdreport)) {
			$mdreport = json_encode($model->getMonthlyStats());
			$cache->setvalue('mdreport', $mdreport);
		}
		$this->assign('mdreport',			json_decode($mdreport, true));
		
		$cache->save();

		// Add references to CSS and JS files
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'includes.php';
		ArsHelperIncludes::includeMedia(false);
		$document =& JFactory::getDocument();
		
		$document->addScript(JURI::base().'../media/com_ars/js/jquery.jqplot.min.js');
		$document->addScript(JURI::base().'../media/com_ars/js/jqplot.dateAxisRenderer.min.js');
		$document->addScript(JURI::base().'../media/com_ars/js/jqplot.hermite.js');
		$document->addScript(JURI::base().'../media/com_ars/js/jqplot.highlighter.min.js');
		
		JHTML::_('behavior.mootools');

		parent::display();
	}
}