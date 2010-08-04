<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'views'.DS.'base.view.html.php';

class ArsViewCategories extends ArsViewBase
{
	protected function onDisplay()
	{
		$app = JFactory::getApplication();
		$hash = $this->getHash();
		
		// ...filter states
		$this->lists->set('fltTitle',	$app->getUserStateFromRequest($hash.'filter_title',
			'title', null));
		$this->lists->set('fltType',	$app->getUserStateFromRequest($hash.'filter_type',
			'type', null));
		$this->lists->set('fltPublished',$app->getUserStateFromRequest($hash.'filter_published',
			'published', null));

		// Add toolbar buttons
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy', false);
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
		JToolBarHelper::divider();
		JToolBarHelper::back('Back', 'index.php?option='.JRequest::getCmd('option'));

		// Load the select box helper
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';

		// Run the parent method
		parent::onDisplay();
	}

	protected function onAdd()
	{
		// Load the select box helper
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';
		parent::onAdd();
	}
}