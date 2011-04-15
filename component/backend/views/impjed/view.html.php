<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'includes.php';
		
		// Set the view title
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD').' &ndash; <small>'.JText::_('ARS_TITLE_IMPORT_JED').'</small>','ars');
		
		// Add toolbar buttons
		JToolBarHelper::back(version_compare(JVERSION,'1.6.0','ge') ? 'JTOOLBAR_BACK' : 'Back', 'index.php?option='.JRequest::getCmd('option'));
		
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
		
		// Include component's CSS and JS files
		ArsHelperIncludes::includeMedia();
		
		// Repeat after me: "Joomla! 1.6.2 and later is a piece of utter crap because it requires me
		// to MANUALLY add this line to make its STANDARD toolbar buttons work". Yes, the PLT is a
		// bunch of morons.
		JHTML::_('behavior.mootools');
		
		parent::display($tpl);
	}
}