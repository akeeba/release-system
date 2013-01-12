<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableEDlidlabel extends FOFTable
{
	public function check() {
		$result = parent::check();
		
		if ($result)
		{
			// Force user_id to be the current user ID in the front-end
			list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();
			if (!$isAdmin && !$isCli)
			{
				$this->user_id = JFactory::getUser()->id;
			}
		}
		
		return $result;
	}
}