<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;

defined('_JEXEC') or die;

/**
 * Integrates with PayPlans 3.x+
 *
 * @package Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration
 */
class PayPlans extends Base
{
	/**
	 * The load priority for this integration.  Lower numbers give higher priority.
	 *
	 * @var  int
	 */
	protected $priority = 200;

	/**
	 * Is this integration available for this site?
	 *
	 * @return  bool  True if available
	 */
	public function isAvailable()
	{
		return defined('PAYPLANS_LOADED');
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
		static $plans = null;

		if (is_null($plans))
		{
			$plans = [];

			$temp = \PayplansApi::getPlans();

			if (!empty($temp))
			{
				foreach ($temp as $item)
				{
					$plans[$temp->id] = $item->title;
				}
			}
		}

		return $plans;
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

		$status = \PayplansStatus::SUBSCRIPTION_ACTIVE;
		// For PayPlans 3.x
		return \PayplansApi::getUser($user_id)->getPlans($status);
	}
}