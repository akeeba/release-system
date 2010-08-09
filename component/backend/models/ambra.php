<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelAmbra extends JModel
{
	/**
	 * Checks if AMBRA.subs is installed
	 */
	static function hasAMBRA()
	{
		static $hasAmbra = null;

		if(is_null($hasAmbra)) {
			jimport('joomla.filesystem.folder');
			$hasAmbra = JFolder::exists(JPATH_ROOT.DS.'components/com_ambrasubs');
		}

		return $hasAmbra;
	}

	/**
	 * Returns a list of all published AMBRA.subs groups (subscription types)
	 * @staticvar array $groupsList
	 * @return array A list of objects: {id, title}
	 */
	function getGroups()
	{
		static $groupsList = null;
		
		if(!is_array($groupsList))
		{
			if(self::hasAMBRA())
			{
				$db = $this->getDBO();
				$query = "SELECT `id`,`title` FROM `#__ambrasubs_types` WHERE `published` = 1";
				$db->setQuery($query);
				$groupsList = $db->loadObjectList();
				if(empty($groupsList)) $groupsList = array();
			}
			else
			{
				$groupsList = array();
			}
		}
		
		return $groupsList;
	}

	/**
	 * Returns a list of AMBRA.subs groups the current user belongs to
	 * @param int $user_id User ID to check. Ommit to use current logged-in user
	 * @return array Array of the group the user belongs to (integers)
	 */
	function getUserGroups($user_id = null)
	{
		if(!self::hasAMBRA()) return array();

		if(is_null($user_id))
		{
			$user = JFactory::getUser();
			$user_id = $user->id;
		}

		$db = $this->getDBO();
		$query = <<<ENDSQL
SELECT
	`typeid`
FROM
	`#__ambrasubs_users2types`
WHERE
	`userid` = $user_id
	AND `status` = 1
	AND `expires_datetime` >= CURRENT_TIMESTAMP
ENDSQL;
		$db->setQuery($query);
		$list = $db->loadResultArray();
		if(empty($list)) $list = array();

		return $list;
	}

}