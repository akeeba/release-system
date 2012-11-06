<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * This file is only necessary for the backend dispatcher not to load 
 */
class ArsDispatcher extends FOFDispatcher
{
	public $defaultView = 'browse';
	
	private $allowedViews = array(
		'browses','categories','downloads','latests','releases','updates'
	);
	
	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();
		if($result) {
			// Load Akeeba Strapper
			include_once JPATH_ROOT.'/media/akeeba_strapper/strapper.php';
			AkeebaStrapper::bootstrap();
			AkeebaStrapper::jQueryUI();
			AkeebaStrapper::addCSSfile('media://com_ars/css/backend.css');
			
			// Default to the "browses" view
			$view = FOFInput::getCmd('view',$this->defaultView, $this->input);
			if(empty($view) || ($view == 'cpanel')) {
				$view = 'browses';
			}
			
			// Set the view, if it's allowed
			FOFInput::setVar('view',$view,$this->input);
			if(!in_array(FOFInflector::pluralize($view), $this->allowedViews)) $result = false;
		}
		
		return $result;
	}
}