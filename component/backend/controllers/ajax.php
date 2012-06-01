<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerAjax extends FOFController
{
	function getfiles()
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/select.php';

		$item_id = FOFInput::getInt('item_id', 0, $this->input);
		$release_id = FOFInput::getInt('release_id', 0, $this->input);
		$selected = FOFInput::getString('selected', '', $this->input);

		$result = ArsHelperSelect::getfiles($selected, $release_id, $item_id, 'filename', array('onchange'=>'onFileChange();'));
		@ob_end_clean;
		echo $result;
		JFactory::getApplication()->close();
	}
}