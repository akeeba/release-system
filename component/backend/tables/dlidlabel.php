<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableEDlidlabel extends F0FTable
{
	public function check() {
		$result = parent::check();

		if ($result)
		{
			// Force user_id to be the current user ID in the front-end
			list($isCli, $isAdmin) = F0FDispatcher::isCliAdmin();
			if (!$isAdmin && !$isCli)
			{
				$this->user_id = JFactory::getUser()->id;
			}
		}

		return $result;
	}
}