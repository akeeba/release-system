<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewImpjed extends JView
{
	public function display($tpl = null)
	{
		// Load helpers
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/select.php';
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/includes.php';
		
		// Set the view title
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD').' &ndash; <small>'.JText::_('ARS_TITLE_IMPORT_JED').'</small>','ars');
		
		// Add toolbar buttons
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$user = JFactory::getUser();
			$perms = (object)array(
				'create'	=> $user->authorise('core.create', 'com_ars'),
				'edit'		=> $user->authorise('core.edit', 'com_ars'),
				'editstate'	=> $user->authorise('core.edit.state', 'com_ars'),
				'delete'	=> $user->authorise('core.delete', 'com_ars'),
			);
		} else {
			$perms = (object)array(
				'create'	=> true,
				'edit'		=> true,
				'editstate'	=> true,
				'delete'	=> true,
			);
		}
		$this->assign('aclperms', $perms);
		$this->perms = $perms;
		
		JToolBarHelper::back(version_compare(JVERSION,'1.6.0','ge') ? 'JTOOLBAR_BACK' : 'Back', 'index.php?option='.JRequest::getCmd('option'));
		
		// Add submenus (those nifty text links below the toolbar!)
		// -- Categories
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=categories';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_CATEGORIES'), $link, (JRequest::getCmd('view','cpanel') == 'categories'));
		// -- Releases
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=releases';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_RELEASES'), $link, (JRequest::getCmd('view','cpanel') == 'releases'));
		// -- Items
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=items';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_ITEMS'), $link, (JRequest::getCmd('view','cpanel') == 'items'));
		
		// Include component's CSS and JS files
		ArsHelperIncludes::includeMedia();
		
		JHTML::_('behavior.mootools');
		
		parent::display($tpl);
	}
}