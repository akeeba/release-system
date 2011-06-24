<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.view');

class ArsViewBase extends JView
{
	protected $lists = null;

	function  __construct($config = array()) {
		parent::__construct($config);
		$this->lists = new JObject();
	}

	function  display($tpl = null)
	{
		// Get the task set in the model
		$model = $this->getModel();
		$task = $model->getState('task','display');

		// Call the relevant method
		$method_name = 'on'.ucfirst($task);
		if(method_exists($this, $method_name)) {
			$this->$method_name();
		} else {
			$this->onDisplay();
		}

		// Add the CSS/JS definitions
		$doc = JFactory::getDocument();
		if($doc->getType() == 'html') {
			require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'includes.php';
			ArsHelperIncludes::includeMedia();
		}
		
		JHTML::_('behavior.mootools');

		// Show the view
		parent::display($tpl);
	}

	protected function onDisplay()
	{
		// Load the model
		$model = $this->getModel();
		$app = JFactory::getApplication();

		// Ordering and filter states handling
		$hash = $this->getHash();

		// ...ordering
		$this->lists->set('order',		$app->getUserStateFromRequest($hash.'filter_order',
			'filter_order', 'id'));
		$this->lists->set('order_Dir',	$app->getUserStateFromRequest($hash.'filter_order_Dir',
			'filter_order_Dir', 'DESC'));

		// Assign data to the view
		$this->assign   ( 'items',		$model->getItemList() );
		$this->assignRef( 'pagination',	$model->getPagination());
		$this->assignRef( 'lists',		$this->lists);

		// Set toolbar title
		$subtitle_key = 'ARS_TITLE_'.strtoupper(JRequest::getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD').' &ndash; <small>'.JText::_($subtitle_key).'</small>','ars');
	}

	protected function onAdd()
	{
		$model = $this->getModel();
		
		$this->assignRef( 'item',		$model->getItem() );	
		// Set toolbar title
		$subtitle_key = 'ARS_TITLE_'.strtoupper(JRequest::getCmd('view','cpanel')).'_EDIT';
		JToolBarHelper::title(JText::_('ARS_TITLE_DASHBOARD').' &ndash; <small>'.JText::_($subtitle_key).'</small>','ars');

		JToolBarHelper::apply();
		JToolBarHelper::save();
		if(version_compare(JVERSION,'1.6.0','ge')) {
			JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		} else {
			$sanTitle = 'Save & New';
			JToolBar::getInstance('toolbar')->appendButton( 'Standard', 'save', $sanTitle, 'savenew', false, false );
		}
		JToolBarHelper::cancel();
	}

	protected function onEdit()
	{
		// An editor is an editor, no matter if the record is new or old :p
		$this->onAdd();
	}

	public final function getHash()
	{
		return JRequest::getCmd('option').'.'.str_replace('View', '', $this->getName()).'.';
	}
}