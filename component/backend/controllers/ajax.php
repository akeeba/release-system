<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerAjax extends FOFController
{
	function getfiles()
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/select.php';

		$item_id = $this->input->getInt('item_id', 0);
		$release_id = $this->input->getInt('release_id', 0);
		$selected = $this->input->getString('selected', '');

		$result = ArsHelperSelect::getfiles($selected, $release_id, $item_id, 'filename', array('onchange'=>'onFileChange();'));
		@ob_end_clean;
		echo $result;
		JFactory::getApplication()->close();
	}
}