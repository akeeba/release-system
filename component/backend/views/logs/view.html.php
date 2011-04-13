<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'views'.DS.'base.view.html.php';

class ArsViewLogs extends ArsViewBase
{
	protected function onDisplay()
	{
		$app = JFactory::getApplication();
		$hash = $this->getHash();
		
		// ...filter states
		$this->lists->set('fltItemText',	$app->getUserStateFromRequest($hash.'filter_itemtext',
			'itemtext', null));
		$this->lists->set('fltUserText',	$app->getUserStateFromRequest($hash.'filter_usertext',
			'usertext', null));
		$this->lists->set('fltReferer',		$app->getUserStateFromRequest($hash.'filter_referer',
			'referer', null));
		$this->lists->set('fltIP',		$app->getUserStateFromRequest($hash.'filter_ip',
			'ip', null));
		$this->lists->set('fltCountry',		$app->getUserStateFromRequest($hash.'filter_country',
			'country', null));
		$this->lists->set('fltAuthorized',		$app->getUserStateFromRequest($hash.'filter_authorized',
			'authorized', null));
		$this->lists->set('fltCategory',	$app->getUserStateFromRequest($hash.'filter_category',
			'category', null));
		$this->lists->set('fltVersion',		$app->getUserStateFromRequest($hash.'filter_version',
			'version', null));

		// Add toolbar buttons
		JToolBarHelper::deleteList();
		JToolBarHelper::divider();
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

		// Load the select box helper
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';

		// Run the parent method
		parent::onDisplay();
	}
}