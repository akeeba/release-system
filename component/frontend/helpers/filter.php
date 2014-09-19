<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsHelperFilter
{
	/**
	 * Filters a list
	 *
	 * @param   array  $source  The source list
	 *
	 * @return  array  The filtered list
	 */
	static public function filterList($source)
	{
		static $myGroups = null;

		// Initialise filtered list
		$list = array();

		// Check for empty source lists
		if (!is_array($source))
		{
			return $list;
		}
		if (empty($source))
		{
			return $list;
		}

		// Load Filtering
		require_once JPATH_ROOT . '/components/com_ars/helpers/filtering.php';

		// Cache user access and groups
		if (is_null($myGroups))
		{
			// Get subscription groups of current user
			if (!ArsHelperFiltering::hasSubscriptionsExtension())
			{
				$mygroups = array();
			}
			else
			{
				$mygroups = ArsHelperFiltering::getUserGroups(JFactory::getUser()->id);
			}
		}

		// Do the real filtering
		foreach ($source as $s)
		{
			// Filter by subscription group
			if (!empty($s->groups))
			{
				// Category defines subscriptions groups, user belongs to none, do
				// not display anything.
				if (empty($mygroups))
				{
					continue;
				}

				// Check if any of the category's subscriptions groups are in the
				// list of groups the user belongs to
				$groups = explode(',', $s->groups);
				$inGroups = false;
				if (!empty($groups))
				{
					foreach ($groups as $group)
					{
						if (in_array($group, $mygroups))
						{
							$inGroups = true;
						}
					}
				}
				else
				{
					$inGroups = true;
				}
				if (!$inGroups)
				{
					continue;
				}
			}

			$list[] = $s;
		}

		return $list;
	}

	/**
	 * Formats a string to a valid Download ID format. If the string is not looking like a Download ID it will return
	 * false to indicate the error.
	 *
	 * @param   string  $dlid The string to reformat.
	 *
	 * @return  bool|string
	 */
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
			if (strlen($dlid) > 32)
			{
				$dlid = substr($dlid, 0, 32);
			}
		}

		return (is_null($user_id) ? '' : $user_id . ':') . $dlid;
	}

	/**
	 * Gets the user associated with a specific Download ID
	 *
	 * @param   string  $dlid The Download ID to check
	 *
	 * @return  array  The user record of the corresponding user and the Download ID
	 *
	 * @throws  Exception  An exception is thrown if the Download ID is invalid or empty
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

		/** @var ArsModelDlidlabels $model */
		$model = F0FModel::getTmpInstance('Dlidlabels', 'ArsModel');

		if (strstr($dlid, ':') !== false)
		{
			$parts = explode(':', $dlid, 2);
			$user_id = (int)$parts[0];
			$dlid = $parts[1];
		}

		$model->primary(1);
		$model->dlid($dlid);

		if (!is_null($user_id))
		{
			$model->primary(0);
			$model->user_id($user_id);
		}

		$matchingRecord = $model->getFirstItem(true);

		if (!is_object($matchingRecord) || empty($matchingRecord->dlid))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		if (!is_null($user_id) && ($user_id != $matchingRecord->user_id))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		if ($matchingRecord->dlid != $dlid)
		{
			throw new Exception('Invalid Download ID', 403);
		}

		return JFactory::getUser($matchingRecord->user_id);
	}

	/**
	 * Returns the main download ID for a user. If it doesn't exist it creates a new one.
	 *
	 * @return mixed
	 */
	static public function myDownloadID()
	{
		$user = JFactory::getUser();

		if ($user->guest)
		{
			return '';
		}

		/** @var ArsModelDlidlabels $model */
		$model = F0FModel::getTmpInstance('Dlidlabels', 'ArsModel');
		$dlidRecord = $model->user_id($user->id)->primary(1)->getFirstItem(true);

		// Create a new main Download ID if none is saved
		if (!is_object($dlidRecord) || empty($dlidRecord->dlid))
		{
			$data = array(
				'user_id' => $user->id,
				'primary' => 1,
				'enabled' => 1,
			);
			$model->save($data);
			$dlidRecord = $model->getSavedTable();
		}

		return $dlidRecord->dlid;
	}
}