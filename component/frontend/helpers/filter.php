<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsHelperFilter
{
	/**
	 * Filters a list
	 * @param array $source The source list
	 * @return array The filtered list
	 */
	static public function filterList($source)
	{
		static $myGroups = null;

		// Initialise filtered list
		$list = array();

		// Check for empty source lists
		if(!is_array($source)) return $list;
		if(empty($source)) return $list;

		// Load Filtering
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/filtering.php';

		// Cache user access and groups
		if(is_null($myGroups))
		{
			// Get subscription groups of current user
			if(!ArsHelperFiltering::hasSubscriptionsExtension()) {
				$mygroups = array();
			} else {
				$mygroups = ArsHelperFiltering::getUserGroups(JFactory::getUser()->id);
			}
		}

		// Do the real filtering
		foreach($source as $s)
		{
			// Filter by subscription group
			if(!empty($s->groups))
			{
				// Category defines subscriptions groups, user belongs to none, do
				// not display anything.
				if(empty($mygroups)) continue;

				// Check if any of the category's subscriptions groups are in the
				// list of groups the user belongs to
				$groups = explode(',', $s->groups);
				$inGroups = false;
				if(!empty($groups)) foreach($groups as $group)
				{
					if(in_array($group, $mygroups)) $inGroups = true;
				}
				else
				{
					$inGroups = true;
				}
				if(!$inGroups) continue;
			}

			$list[] = $s;
		}

		return $list;
	}

	static public function reformatDownloadID($dlid)
	{
		// Check if the Download ID is empty or consists of only whitespace
		if (empty($dlid))
		{
			return false;
		}

		$dlid = trim($dlid);

		if (empty($dlid))
		{
			return false;
		}

		// Is the Download ID too short?
		if (strlen($dlid) < 32)
		{
			return false;
		}

		// Do we have a userid:downloadid format?
		$user_id = null;
		if (strstr($dlid, ':') !== false)
		{
			$parts = explode(':', $dlid, 2);
			$user_id = (int)$parts[0];
			if ($user_id <= 0)
			{
				$user_id = null;
			}
			if (isset($parts[1]))
			{
				$dlid = $parts[1];
			}
			else
			{
				return false;
			}
		}

		// Trim the Download ID
		if (strlen($dlid) > 32)
		{
			if(strlen($dlid) > 32) $dlid = substr($dlid,0,32);
		}

		return (is_null($user_id) ? '' : $user_id.':') . $dlid;
	}

	/**
	 * Gets the user associated with a specific Download ID
	 *
	 * @param   string  $dlid  The Download ID to check
	 *
	 * @return  array  The user record of the corresponding user and the Download ID
	 *
	 * @throws Exception An exception is thrown if the Download ID is invalid or empty
	 */
	static public function getUserFromDownloadID($dlid)
	{
		// Reformat the Download ID
		$dlid = self::reformatDownloadID($dlid);

		if ($dlid === false)
		{
			throw new Exception('Invalid Download ID', 403);
		}

		// Do we have a userid:downloadid format?
		$user_id = null;
		if (strstr($dlid, ':') !== false)
		{
			$parts = explode(':', $dlid, 2);
			$user_id = (int)$parts[0];
			$dlid = $parts[1];
		}

		if (is_null($user_id))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('id')
				))
				->from($db->qn('#__users'))
				->where('md5(concat('.$db->qn('id').','.$db->qn('username').','.$db->qn('password').')) = '.$db->q($dlid));
			$db->setQuery($query);
			$user_id = $db->loadResult();
		}
		else
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select(array(
					'label'
				))->from($db->qn('#__ars_dlidlabels'))
				->where($db->qn('user_id').' = '.$db->q($user_id))
				->where($db->qn('enabled').' = '.$db->q(1));
			$db->setQuery($query);
			$labels = $db->loadColumn();

			if (empty($labels))
			{
				throw new Exception('Invalid Download ID', 403);
			}

			$query = $db->getQuery(true)
				->select(array(
					'md5(concat('.$db->qn('id').','.$db->qn('username').','.$db->qn('password').')) AS '.$db->qn('dlid')
				))
				->from($db->qn('#__users'))
				->where($db->qn('id').' = '.$db->q($user_id));
			$db->setQuery($query);
			$masterDlid = $db->loadResult();

			$found = false;
			foreach($labels as $k => $label)
			{
				$check = md5($user_id . $label . $masterDlid);
				if ($check == $dlid)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				throw new Exception('Invalid Download ID', 403);
			}
		}

		return JFactory::getUser($user_id);
	}

	static public function myDownloadID()
	{
		$user = JFactory::getUser();

		if ($user->guest)
		{
			return '';
		}

		return md5($user->id . $user->username . $user->password);
	}
}