<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;

use FOF30\Container\Container;

defined('_JEXEC') or die;

/**
 * Integrates with Akeeba Subscription 5.x+ based on FOF 3.x
 *
 * @package Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration
 */
class AkeebaSubsFive extends Base
{
	/**
	 * The load priority for this integration.  Lower numbers give higher priority.
	 *
	 * @var  int
	 */
	protected $priority = 80;

	/**
	 * The name of the supported component.
	 *
	 * @var string
	 */
	protected $componentName = 'com_akeebasubs';

	/**
	 * Is this integration available for this site?
	 *
	 * @return  bool  True if available
	 */
	public function isAvailable()
	{
		$result = parent::isAvailable();

		if (!$result)
		{
			return false;
		}

		\JLoader::import('joomla.filesystem.folder');

		// Akeeba Subscriptions 5.0+ does not have the admin views folder any more
		if (\JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $this->componentName . '/views'))
		{
			return false;
		}

		return true;
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
	public function getAllGroups()
	{
		static $theList = null;

		if (is_null($theList))
		{
			$theList = array();

			$container = Container::getInstance('com_akeebasubs');

			/** @var \Akeeba\Subscriptions\Admin\Model\Levels $levelsModel */
			$levelsModel = $container->factory->model('Levels')->tmpInstance();
			$list = $levelsModel->get(true);

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
	public function getGroupsForUser($user_id)
	{
		// If we are not logged in we don't have any active subscriptions.
		if (empty($user_id))
		{
			return [];
		}

		$container = Container::getInstance('com_akeebasubs');
		$subscriptionsModel = $container->factory->model('Subscriptions')->tmpInstance();
		$rawList = $subscriptionsModel->enabled(1)->user_id($user_id)->get(true);

		$theList = array();

		if ($rawList->count())
		{
			foreach ($rawList as $item)
			{
				$theList[] = $item->akeebasubs_level_id;
			}
		}

		return array_unique($theList);
	}
}