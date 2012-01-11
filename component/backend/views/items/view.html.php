<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.'/views/base.view.html.php';

class ArsViewItems extends ArsViewBase
{
	protected function onDisplay()
	{
		$app = JFactory::getApplication();
		$hash = $this->getHash();
		
		// ...filter states
		$this->lists->set('fltCategory',	$app->getUserStateFromRequest($hash.'filter_category',
			'category', null));
		$this->lists->set('fltRelease',		$app->getUserStateFromRequest($hash.'filter_release',
			'release', null));
		$this->lists->set('fltPublished',$app->getUserStateFromRequest($hash.'filter_published',
			'published', null));
		
		// Add toolbar buttons
		if($this->perms->editstate) {
			JToolBarHelper::publishList();
			JToolBarHelper::unpublishList();
			JToolBarHelper::divider();
		}
		if($this->perms->create) {
			JToolBarHelper::custom( 'copy', 'copy.png', 'copy_f2.png', 'ARS_GLOBAL_COPY', false);
		}
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editListX();
		}
		if($this->perms->create) {
			JToolBarHelper::addNewX();
		}
		JToolBarHelper::divider();
		JToolBarHelper::back(version_compare(JVERSION,'1.6.0','ge') ? 'JTOOLBAR_BACK' : 'Back', 'index.php?option='.JRequest::getCmd('option'));

		// Add submenus (those nifty text links below the toolbar!)
		// -- cpanel
		$link = JURI::base().'?option='.JRequest::getCmd('option');
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_GOTODASHBOARD'), $link, (JRequest::getCmd('view','cpanel') == 'cpanel'));
		// -- Categories
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=categories';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_CATEGORIES'), $link, (JRequest::getCmd('view','cpanel') == 'categories'));
		// -- Releases
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=releases';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_RELEASES'), $link, (JRequest::getCmd('view','cpanel') == 'releases'));
		// -- Items
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=items';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_ITEMS'), $link, (JRequest::getCmd('view','cpanel') == 'items'));
		if($this->perms->create) {
			// -- Import
			$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=impjed';
			JSubMenuHelper::addEntry(JText::_('ARS_TITLE_IMPORT_JED'), $link, (JRequest::getCmd('view','cpanel') == 'impjed'));
		}
		// -- Environments
		$link = JURI::base().'?option='.JRequest::getCmd('option').'&view=environments';
		JSubMenuHelper::addEntry(JText::_('ARS_TITLE_ENVIRONMENTS'), $link);
		
		// Load the select box helper
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/select.php';

		// Run the parent method
		parent::onDisplay();
	}

	protected function onAdd()
	{
		// Load the select box helper
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/select.php';
		parent::onAdd();
		
		$model = $this->getModel();
		$fltRelease		= $model->getState('release', null, 'int');

		if($fltRelease) {
			$item = $model->getItem();
			$item->release_id = $fltRelease;
			$this->assignRef( 'item',		$item );
		}
	}
}