<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;

defined('_JEXEC') or die;

/**
 * Abstract base class for integrations with subscription extensions / e-commerce extensions / subscription services.
 * All custom integrations should extend this class or at least implement the IntegrationInterface. Please note that
 * custom integrations MUST be placed in this directory.
 */
abstract class Base implements IntegrationInterface
{
	/**
	 * The load priority for this integration.  Lower numbers give higher priority. If two or more integrations are
	 * available at the same time the one with the highest priority will be used by ARS.
	 *
	 * @var  int
	 */
	protected $priority = 100;

	/**
	 * The name of the supported component. If you are not writing a component integration override isAvailable().
	 *
	 * @var string
	 */
	protected $componentName = 'com_example';

	/**
	 * Is this integration available for this site? By default it looks if the specified component is installed and
	 * enabled. If you want to do custom detection please override.
	 *
	 * @return  bool  True if available
	 */
	public function isAvailable()
	{
		if (empty($this->componentName))
		{
			return false;
		}

		\JLoader::import('joomla.filesystem.folder');

		if (!\JFolder::exists(JPATH_ROOT . '/components/' . $this->componentName))
		{
			return false;
		}

		\JLoader::import('cms.application.component.helper');

		if (!\JComponentHelper::getComponent('com_akeebasubs', true)->enabled)
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns the priority of this integration. Lower numbers give higher priority. If two or more integrations are
	 * available at the same time the one with the highest priority will be used by ARS.
	 *
	 * @return  mixed
	 */
	final public function getPriority()
	{
		return $this->priority;
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
		return [];
	}

	/**
	 * Returns a list of all currently active subscription levels for a specific Joomla! user ID. The return is an array
	 * of integers, e.g. [1, 2, 5, 8]
	 *
	 * @return  array
	 */
	public function getGroupsForUser($user_id)
	{
		return [];
	}
}