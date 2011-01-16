<?php

/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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
}