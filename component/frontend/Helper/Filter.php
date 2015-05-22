<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

use Akeeba\ReleaseSystem\Site\Model\DownloadIDLabels;
use Akeeba\ReleaseSystem\Site\Model\SubscriptionIntegration;
use FOF30\Container\Container;
use FOF30\Model\DataModel\Collection;

defined('_JEXEC') or die();

abstract class Filter
{
	/**
	 * Filters a list
	 *
	 * @param   Collection  $source  The source list
	 *
	 * @return  array  The filtered list
	 */
	static public function filterList(Collection $source)
	{
		static $myGroups = null;

		// Initialise filtered list
		$list = array();

		// If we pass a DataModel we'll convert it to a Collection

		// Check for empty source lists
		if (!is_object($source) || !($source instanceof Collection))
		{
			return $list;
		}

		if (!$source->count())
		{
			return $list;
		}

		// Cache user access and groups
		if (is_null($myGroups))
		{
			$container = Container::getInstance('com_ars');
			/** @var SubscriptionIntegration $subsIntegration */
			$subsIntegration = $container->factory->model('SubscriptionIntegration');

			// Get subscription groups of current user
			if (!$subsIntegration->hasIntegration())
			{
				$mygroups = array();
			}
			else
			{
				$mygroups = $subsIntegration->getUserGroups();
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
				$groups = $s->groups;

				if (!is_array($groups))
				{
					$groups = explode(',', $groups);
				}

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
	 * @param   string  $downloadId The Download ID to check
	 *
	 * @return  \JUser  The user record of the corresponding user and the Download ID
	 *
	 * @throws  \Exception  An exception is thrown if the Download ID is invalid or empty
	 */
	static public function getUserFromDownloadID($downloadId)
	{
		// Reformat the Download ID
		$downloadId = self::reformatDownloadID($downloadId);

		if ($downloadId === false)
		{
			throw new \Exception('Invalid Download ID', 403);
		}

		// Do we have a userid:downloadid format?
		$user_id = null;

		$container = Container::getInstance('com_ars');
		/** @var DownloadIDLabels $model */
		$model = $container->factory->model('DownloadIDLabels')->tmpInstance();

		if (strstr($downloadId, ':') !== false)
		{
			$parts = explode(':', $downloadId, 2);
			$user_id = (int)$parts[0];
			$downloadId = $parts[1];
		}

		$model->primary(1);
		$model->dlid($downloadId);

		if (!is_null($user_id))
		{
			$model->primary(0);
			$model->user_id($user_id);
		}

		try
		{
			$matchingRecord = $model->firstOrFail();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Invalid Download ID', 403);
		}

		if (!is_object($matchingRecord) || empty($matchingRecord->dlid))
		{
			throw new \Exception('Invalid Download ID', 403);
		}

		if (!is_null($user_id) && ($user_id != $matchingRecord->user_id))
		{
			throw new \Exception('Invalid Download ID', 403);
		}

		if ($matchingRecord->dlid != $downloadId)
		{
			throw new \Exception('Invalid Download ID', 403);
		}

		return \JFactory::getUser($matchingRecord->user_id);
	}

	/**
	 * Returns the main download ID for a user. If it doesn't exist it creates a new one.
	 *
	 * @return mixed
	 */
	static public function myDownloadID($user_id = null)
	{
		$user = \JFactory::getUser($user_id);

		if ($user->guest)
		{
			return '';
		}

		$container = Container::getInstance('com_ars');
		/** @var DownloadIDLabels $model */
		$model = $container->factory->model('DownloadIDLabels')->tmpInstance();
		$dlidRecord = $model->user_id($user->id)->primary(1)->firstOrCreate([
			'user_id' => $user->id,
			'primary' => 1,
			'enabled' => 1,
		]);

		return $dlidRecord->dlid;
	}
}