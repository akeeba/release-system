<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD'));
		//JToolBarHelper::preferences('com_ars', '550');

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

		$this->assign('icondefs', $model->getIconDefinitions()); // Icon definitions

		// Add references to CSS and JS files
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'includes.php';
		ArsHelperIncludes::includeMedia(false);

		parent::display();
	}
}