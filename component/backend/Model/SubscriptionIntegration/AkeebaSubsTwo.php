<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;

use FOF30\Date\Date;

defined('_JEXEC') or die;

/**
 * Integrates with Akeeba Subscription 2.x to 4.x based on FOF 2.x
 *
 * @package Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration
 */
class AkeebaSubsTwo extends Base
{
	/**
	 * The load priority for this integration.  Lower numbers give higher priority.
	 *
	 * @var  int
	 */
	protected $priority = 100;

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
		if (!\JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $this->componentName . '/views'))
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

			\JLoader::import('joomla.filesystem.folder');
			\JLoader::import('joomla.filesystem.file');
			\JLoader::import('f0f.include');

			$rawList = \F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
							   ->enabled('')
							   ->limit(0)
							   ->limitstart(0)
							   ->getList();

			if (!empty($rawList))
			{
				foreach ($rawList as $item)
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

		\JLoader::import('f0f.include');

		\JLoader::import('joomla.utilities.date');
		$jNow = new Date();

		\JLoader::import('joomla.filesystem.folder');
		\JLoader::import('joomla.filesystem.file');
		$rawList = \F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel', array('table' => 'subscriptions', 'input' => array('option' => 'com_akeebasubs')))
						   ->enabled(1)
						   ->user_id($user_id)
						   ->skipOnProcessList(1)
						   ->getList();

		$theList = array();

		foreach ($rawList as $item)
		{
			$theList[] = $item->akeebasubs_level_id;
		}

		return array_unique($theList);
	}
}