<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration\IntegrationInterface;
use FOF30\Container\Container;
use FOF30\Model\Model;

/**
 * Used to filter by subscription level
 */
class SubscriptionIntegration extends Model
{
	/** @var  IntegrationInterface  Integration object */
	protected $integration = null;

	/** @var  array  Cached of subscription levels per user ID */
	protected $userGroups = [];

	/**
	 * Constructs the model. Also sets the protected $integration property to the object of the active integration (or
	 * null if none is available).
	 *
	 * @param   Container  $container  The component's DI container
	 * @param   array      $config     Configuration overrides
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->integration = $this->getIntegration();
	}

	/**
	 * Do I have an integration with a subscriptions extension / service?
	 *
	 * @return  bool  True if I have an integration
	 */
	public function hasIntegration()
	{
		return is_object($this->integration);
	}

	/**
	 * Returns a list of subscription groups / levels
	 *
	 * @return  array
	 */
	public function getGroups()
	{
		static $cached = null;

		if (!is_object($this->integration))
		{
			return [];
		}

		if (is_null($cached))
		{
			$cached = $this->integration->getAllGroups();
		}

		return $cached;
	}

	/**
	 * Returns a list of subscription groups / levels in a format suitable for selection lists
	 *
	 * @return  array
	 */
	static function getGroupsForSelect()
	{
		/** @var self $instance */
		$instance = Container::getInstance('com_ars')->factory->model('SubscriptionIntegration');

		$ret = [];
		$temp = $instance->getGroups();

		if (!empty($temp))
		{
			foreach ($temp as $k => $v)
			{
				$ret[] = [
					'key' => $k,
					'value' => $v
				];
			}
		}

		return $ret;
	}

	/**
	 * Returns a list of subscription groups/levels the current user belongs to
	 *
	 * @param   int  $user_id  User ID to check. Leave null to use current logged-in user.
	 *
	 * @return  array  Array of integers: the subscription levels the user belongs to
	 */
	public function getUserGroups($user_id = null)
	{
		if (!is_object($this->integration))
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
			$this->userGroups[$user_id] = $this->integration->getGroupsForUser($user_id);
		}

		return $this->userGroups[$user_id];
	}

	/**
	 * Looks for integrations with subscription extensions and returns the one you need to use
	 *
	 * @return  IntegrationInterface|null
	 */
	protected function getIntegration()
	{
		/** @var IntegrationInterface $integration */
		$integration = null;

		$dh = new \DirectoryIterator(__DIR__ . '/SubscriptionIntegration');

		/** @var \DirectoryIterator $file */
		foreach ($dh as $file)
		{
			if (!$file->isFile())
			{
				continue;
			}

			if ($file->getExtension() != 'php')
			{
				continue;
			}

			if (in_array($file->getBasename('.php'), ['IntegrationInterface', 'Base']))
			{
				continue;
			}

			$className = '\\Akeeba\\ReleaseSystem\\Admin\\Model\\SubscriptionIntegration\\' . $file->getBasename('.php');

			if (!class_exists($className))
			{
				continue;
			}

			/** @var IntegrationInterface $o */
			$o = new $className;

			if (!$o->isAvailable())
			{
				continue;
			}

			if (!is_object($integration) || ($o->getPriority() < $integration->getPriority()))
			{
				$integration = $o;
			}
		}

		return $integration;
	}
}