<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class ArsControllerAjax extends JController
{
	function getfiles()
	{
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'select.php';

		$item_id = JRequest::getInt('item_id',0);
		$release_id = JRequest::getInt('release_id',0);
		$selected = JRequest::getString('selected', '');

		$result = ArsHelperSelect::getfiles($selected, $release_id, $item_id, 'filename', array('onchange'=>'onFileChange();'));
		@ob_end_clean;
		echo $result;
		die();
	}
}