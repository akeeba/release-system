<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

use Akeeba\ReleaseSystem\Site\Model\DownloadIDLabels;
use Akeeba\ReleaseSystem\Site\Model\SubscriptionIntegration;
use Exception;
use FOF30\Container\Container;
use FOF30\Model\DataModel;
use Joomla\CMS\User\User;

defined('_JEXEC') or die();

abstract class Filter
{
	/**
	 * Used to filter a list by subscription levels
	 *
	 * @param DataModel  $source              The source item to check whether it should be included in the list
	 * @param bool       $displayUnauthorized Do we want to display such item to unauthorized users, too?
	 * @param array|null $filterByViewLevels  View levels of the user
	 *
	 * @return  bool True if we should add it to the list, false otherwise
	 */
	public static function filterItem(DataModel $source, bool $displayUnauthorized = false, ?array $filterByViewLevels = null): bool
	{
		static $myGroups = null;

		if (!is_object($source) || !($source instanceof DataModel))
		{
			return false;
		}

		// If we're told to display unauthorized links for this item we have to oblige
		if ($source->show_unauth_links && $displayUnauthorized)
		{
			return true;
		}

		// Should I also filter by a list of view access levels?
		if (is_array($filterByViewLevels) && !in_array($source->access, $filterByViewLevels))
		{
			return false;
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
				$mygroups = [];
			}
			else
			{
				$mygroups = $subsIntegration->getUserGroups();
			}
		}

		// Filter by subscription group
		if (!empty($source->groups))
		{
			// Category defines subscriptions groups, user belongs to none, do
			// not display anything.
			if (empty($mygroups))
			{
				return false;
			}

			// Check if any of the category's subscriptions groups are in the
			// list of groups the user belongs to
			$groups = $source->groups;

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
				return false;
			}
		}

		return true;
	}

	/**
	 * Formats a string to a valid Download ID format. If the string is not looking like a Download ID it will return
	 * an empty string instead.
	 *
	 * @param string $dlid The string to reformat.
	 *
	 * @return  string
	 */
	public static function reformatDownloadID(string $dlid): string
	{
		$dlid = trim($dlid);

		// Is the Download ID empty or too short?
		if (empty($dlid) || (strlen($dlid) < 32))
		{
			return '';
		}

		// Do we have a userid:downloadid format?
		$user_id = null;

		if (strstr($dlid, ':') !== false)
		{
			$parts   = explode(':', $dlid, 2);
			$user_id = (int) $parts[0];

			if ($user_id <= 0)
			{
				$user_id = null;
			}

			if (!isset($parts[1]))
			{
				return '';
			}

			$dlid = $parts[1];
		}

		// Trim the Download ID
		if (strlen($dlid) > 32)
		{
			$dlid = substr($dlid, 0, 32);
		}

		return (is_null($user_id) ? '' : $user_id . ':') . $dlid;
	}

	/**
	 * Gets the user associated with a specific Download ID
	 *
	 * @param string $downloadId The Download ID to check
	 *
	 * @return  User  The user record of the corresponding user and the Download ID
	 *
	 * @throws  Exception  An exception is thrown if the Download ID is invalid or empty
	 */
	static public function getUserFromDownloadID($downloadId): User
	{
		// Reformat the Download ID
		$downloadId = self::reformatDownloadID($downloadId);

		if (empty($downloadId))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		// Do we have a userid:downloadid format?
		$user_id = null;

		$container = Container::getInstance('com_ars');
		/** @var DownloadIDLabels $model */
		$model = $container->factory->model('DownloadIDLabels')->tmpInstance();

		if (strstr($downloadId, ':') !== false)
		{
			$parts      = explode(':', $downloadId, 2);
			$user_id    = (int) $parts[0];
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
		catch (Exception $e)
		{
			throw new Exception('Invalid Download ID', 403);
		}

		if (!is_object($matchingRecord) || empty($matchingRecord->dlid))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		if (!is_null($user_id) && ($user_id != $matchingRecord->user_id))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		if ($matchingRecord->dlid != $downloadId)
		{
			throw new Exception('Invalid Download ID', 403);
		}

		return $container->platform->getUser($matchingRecord->user_id);
	}

	/**
	 * Returns the main download ID for a user. If it doesn't exist it creates a new one.
	 *
	 * @param int|null $user_id The Joomla user ID
	 *
	 * @return string
	 */
	static public function myDownloadID(?int $user_id = null): string
	{
		$container = Container::getInstance('com_ars');
		$user      = $container->platform->getUser($user_id);

		if ($user->guest)
		{
			return '';
		}

		/** @var DownloadIDLabels $model */
		$model      = $container->factory->model('DownloadIDLabels')->tmpInstance();
		$dlidRecord = $model->user_id($user->id)->primary(1)->firstOrCreate([
			'user_id' => $user->id,
			'primary' => 1,
			'enabled' => 1,
		]);

		return $dlidRecord->dlid;
	}
}
