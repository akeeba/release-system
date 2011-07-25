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
	function  display($tpl = null) {
		$model = $this->getModel();
		$task = $model->getState('task','cmd');
		
		// Include the Chameleon helper
		require_once dirname(__FILE__).DS.'..'.DS.'helpers'.DS.'chameleon.php';

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
			require_once JPATH_COMPONENT.DS.'helpers'.DS.'includes.php';
			ArsHelperIncludes::includeMedia();
		}

		// Pass the data
		$this->assignRef( 'items',		$model->itemList );
		$this->assignRef( 'item',		$model->item );
		$this->assignRef( 'lists',		$model->lists );
		$this->assignRef( 'pagination',	$model->pagination );

		// Pass the parameters
		$app = JFactory::getApplication();
		$params =& $app->getPageParameters('com_ars');
		$this->assignRef( 'params',		$params );

		parent::display($tpl);
	}
	
	function getSubLayout($layout, $altview = null)
	{
		$file = $layout;
		$file = preg_replace('/[^A-Z0-9_\.-]/i', '', $file);
		
		if(is_null($altview)) {
			$altview = $this->getName();
		}

		$path = $this->_basePath. '/views/' . strtolower($altview) . '/tmpl';
		$template = JFactory::getApplication()->getTemplate();
		$altpath = JPATH_ROOT.'/templates/'.$template.'/html/com_ars/'.strtolower($altview);

		jimport('joomla.filesystem.path');
		$filetofind	= $this->_createFileName('template', array('name' => $file));
		$subtemplate = JPath::find($altpath, $filetofind);
		if($subtemplate == false) {
			$subtemplate = JPath::find($path, $filetofind);
		}
		if($subtemplate == false) {
			$filetofind = $this->_createFileName('', array('name' => 'default'));
			$subtemplate = JPath::find($altpath, $filetofind);
		}
		if($subtemplate == false) {
			$subtemplate = JPath::find($path, $filetofind);
		}

		return $subtemplate;
	}
}