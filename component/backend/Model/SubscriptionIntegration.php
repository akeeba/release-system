<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use FOF30\Model\Model;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Folder;

/**
 * Used to filter by subscription level
 */
class SubscriptionIntegration extends Model
{
	/** @var  array  Cached of subscription levels per user ID */
	protected $userGroups = [];

	/** @var bool|null Do I have a compatible Akeeba Subscriptions version? */
	protected $hasAkeebaSubs;

	/** @var array|null Cached subscription groups */
	protected $cachedGroups;

	/**
	 * Subscriptions levels per user ID, cached for performance
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	protected static $groupsPerUser = [];

	/**
	 * Returns a list of subscription groups / levels in a format suitable for selection lists
	 *
	 * @return  array
	 */
	public static function getGroupsForSelect(): array
	{
		/** @var self $instance */
		$instance = Container::getInstance('com_ars')->factory->model('SubscriptionIntegration');

		$ret  = [];
		$temp = $instance->getGroups();

		if (!empty($temp))
		{
			foreach ($temp as $k => $v)
			{
				$ret[] = [
					'value' => $k,
					'text'  => $v,
				];
			}
		}

		return $ret;
	}

	/**
	 * Is the Akeeba Subscriptions integration available for this site?
	 *
	 * @return  bool  True if available
	 */
	public function hasIntegration(): bool
	{
		if (!is_null($this->hasAkeebaSubs))
		{
			return $this->hasAkeebaSubs;
		}

		$this->hasAkeebaSubs = false;

		if (!Folder::exists(JPATH_ROOT . '/components/com_akeebasubs'))
		{
			return false;
		}

		if (!ComponentHelper::getComponent('com_akeebasubs', true)->enabled)
		{
			return false;
		}

		// Akeeba Subscriptions 5.0+ does not have the admin views folder any more
		if (Folder::exists(JPATH_ADMINISTRATOR . '/components/com_akeebasubs/views'))
		{
			return false;
		}

		$this->hasAkeebaSubs = true;

		return true;
	}

	/**
	 * Returns a list of subscription groups / levels
	 *
	 * @return  array
	 */
	public function getGroups(): array
	{
		if (!$this->hasIntegration())
		{
			return [];
		}

		if (!is_null($this->cachedGroups))
		{
			return $this->cachedGroups;
		}

		$this->cachedGroups = $this->getAllGroups();

		return $this->cachedGroups;
	}

	/**
	 * Returns a list of subscription groups/levels the current user belongs to
	 *
	 * @param   int  $user_id  User ID to check. Leave null to use current logged-in user.
	 *
	 * @return  array  Array of integers: the subscription levels the user belongs to
	 */
	public function getUserGroups(?int $user_id = null): array
	{
		if (!$this->hasIntegration())
		{
			return [];
		}

		if (empty($user_id))
		{
			$user_id = $this->container->platform->getUser()->id;
		}

		// Seriously, if we still don't have a user ID we can't have any subscription!
		if (empty($user_id))
		{
			return [];
		}

		if (!isset($this->userGroups[$user_id]))
		{
			$this->userGroups[$user_id] = $this->getGroupsForUser($user_id);
		}

		return $this->userGroups[$user_id];
	}

	/**
	 * Returns a list of all possible subscription levels. The return is an array of arrays in the format:
	 * [
	 *    1 => 'Description for first level',
	 *    2 => 'Description for second level',
	 *    ...
	 * ]
	 *
	 * @return  array
	 */
	private function getAllGroups(): array
	{
		static $theList = null;

		if (is_null($theList))
		{
			$theList = [];

			$container = Container::getInstance('com_akeebasubs');

			/** @var DataModel $levelsModel */
			$levelsModel = $container->factory->model('Levels')->tmpInstance();
			$list        = $levelsModel->get(true);

			if ($list->count())
			{
				foreach ($list as $item)
				{
					$theList[$item->akeebasubs_level_id] = $item->title;
				}
			}
		}

		return $theList;
	}

	/**
	 * Returns a list of all currently active subscription levels for a specific Joomla! user ID. The return is an array
	 * of integers, e.g. [1, 2, 5, 8]
	 *
	 * @return  array
	 */
	private function getGroupsForUser(?int $user_id): array
	{
		// If we are not logged in we don't have any active subscriptions.
		if (empty($user_id))
		{
			return [];
		}

		if (array_key_exists($user_id, self::$groupsPerUser))
		{
			return self::$groupsPerUser[$user_id];
		}


		$container = Container::getInstance('com_akeebasubs');
		/** @var DataModel $subscriptionsModel */
		$subscriptionsModel = $container->factory->model('Subscriptions')->tmpInstance();
		/** @var DataModel\Collection $rawList */
		$rawList = $subscriptionsModel->enabled(1)->user_id($user_id)->get(true);

		$theList = [];

		if ($rawList->count())
		{
			foreach ($rawList as $item)
			{
				$theList[] = $item->akeebasubs_level_id;
			}
		}

		self::$groupsPerUser[$user_id] = array_unique($theList);

		return self::$groupsPerUser[$user_id];
	}
}
