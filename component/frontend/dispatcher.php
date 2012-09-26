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
	
	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();
		if(!$result) {
			return $result;
		}
		
		// Load Akeeba Strapper
		include_once JPATH_ROOT.'/media/akeeba_strapper/strapper.php';
		AkeebaStrapper::bootstrap();
		AkeebaStrapper::jQueryUI();
		AkeebaStrapper::addCSSfile('media://com_ars/css/backend.css');

		return true;
	}
}