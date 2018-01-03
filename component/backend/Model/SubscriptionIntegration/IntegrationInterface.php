<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;

defined('_JEXEC') or die;

/**
 * Interface for integrations with subscription extensions / e-commerce extensions / subscription services.
 *
 * @see  Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration\Base  The abstract class custom integrations should extend
 */
interface IntegrationInterface
{
	/**
	 * Is this integration available for this site?
	 *
	 * @return  bool  True if available
	 */
	public function isAvailable();

	/**
	 * Returns the priority of this integration. Lower numbers give higher priority. If two or more integrations are
	 * available at the same time the one with the highest priority will be used by ARS.
	 *
	 * @return  mixed
	 */
	public function getPriority();

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
	public function getAllGroups();

	/**
	 * Returns a list of all currently active subscription levels for a specific Joomla! user ID. The return is an array
	 * of integers, e.g. [1, 2, 5, 8]
	 *
	 * @return  array
	 */
	public function getGroupsForUser($user_id);
}
