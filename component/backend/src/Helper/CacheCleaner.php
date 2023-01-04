<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Helper;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use JConfig;
use Joomla\Application\AbstractApplication;
use Joomla\Application\ConfigurationAwareApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class CacheCleaner
{
	/**
	 * Clears the com_modules and com_plugins cache. You need to call this whenever you alter the publish state or
	 * parameters of a module or plugin from your code.
	 *
	 * @return  void
	 */
	public static function clearPluginsAndModulesCache()
	{
		self::clearPluginsCache();
		self::clearModulesCache();
	}

	/**
	 * Clears the com_plugins cache. You need to call this whenever you alter the publish state or parameters of a
	 * plugin from your code.
	 *
	 * @return  void
	 */
	public static function clearPluginsCache()
	{
		self::clearCacheGroups(['com_plugins'], [0, 1]);
	}

	/**
	 * Clears the com_modules cache. You need to call this whenever you alter the publish state or parameters of a
	 * module from your code.
	 *
	 * @return  void
	 */
	public static function clearModulesCache()
	{
		self::clearCacheGroups(['com_modules'], [0, 1]);
	}

	/**
	 * Clears the specified cache groups.
	 *
	 * @param   array        $clearGroups   Which cache groups to clear. Usually this is com_yourcomponent to clear
	 *                                      your component's cache.
	 * @param   array        $cacheClients  Which cache clients to clear. 0 is the back-end, 1 is the front-end. If you
	 *                                      do not specify anything, both cache clients will be cleared.
	 * @param   string|null  $event         An event to run upon trying to clear the cache. Empty string to disable. If
	 *                                      NULL and the group is "com_content" I will trigger onContentCleanCache.
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public static function clearCacheGroups(array $clearGroups, array $cacheClients = [
		0, 1,
	], ?string $event = null): void
	{
		// Early return on nonsensical input
		if (empty($clearGroups) || empty($cacheClients))
		{
			return;
		}

		// Make sure I have an application object
		try
		{
			$app = Factory::getApplication();
		}
		catch (Exception $e)
		{
			return;
		}

		// If there's no application object things will break; let's get outta here.
		if (!is_object($app))
		{
			return;
		}

		// Loop all groups to clean
		foreach ($clearGroups as $group)
		{
			// Groups must be non-empty strings
			if (empty($group) || !is_string($group))
			{
				continue;
			}

			// Loop all clients (applications)
			foreach ($cacheClients as $client_id)
			{
				$client_id = (int) ($client_id ?? 0);

				$options = self::clearCacheGroup($group, $client_id, $app);

				// Do not call any events if I failed to clean the cache using the core Joomla API
				if (!($options['result'] ?? false))
				{
					return;
				}

				/**
				 * If you're cleaning com_content and you have passed no event name I will use onContentCleanCache.
				 */
				if ($group === 'com_content')
				{
					$cacheCleaningEvent = $event ?: 'onContentCleanCache';
				}

				/**
				 * Call Joomla's cache cleaning plugin event (e.g. onContentCleanCache) as well.
				 *
				 * @see BaseDatabaseModel::cleanCache()
				 */
				if (empty($cacheCleaningEvent))
				{
					continue;
				}


				self::runPlugins($cacheCleaningEvent, $options);
			}
		}
	}

	/**
	 * Clean a cache group
	 *
	 * @param   string  $group      The cache to clean, e.g. com_content
	 * @param   int     $client_id  The application ID for which the cache will be cleaned
	 * @param   object  $app        The current CMS application. DO NOT TYPEHINT MORE SPECIFICALLY!
	 *
	 * @return  array Cache controller options, including cleaning result
	 * @throws  Exception
	 */
	public static function clearCacheGroup(string $group, int $client_id, object $app): array
	{
		// Get the default cache folder. Start by using the JPATH_CACHE constant.
		$cacheBaseDefault = JPATH_CACHE;
		$appClientId      = 0;

		if (method_exists($app, 'getClientId'))
		{
			$appClientId = $app->getClientId();
		}

		// -- If we are asked to clean cache on the other side of the application we need to find a new cache base
		if ($client_id != $appClientId)
		{
			$cacheBaseDefault = (($client_id) ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/cache';
		}

		// Get the cache controller's options
		$options = [
			'defaultgroup' => $group,
			'cachebase'    => self::getAppConfigParam($app, 'cache_path', $cacheBaseDefault),
			'result'       => true,
		];

		try
		{
			$container = Factory::getContainer();

			if (empty($container))
			{
				throw new RuntimeException('Cannot get Joomla 4 application container');
			}

			/** @var CacheControllerFactoryInterface $cacheControllerFactory */
			$cacheControllerFactory = $container->get('cache.controller.factory');

			if (empty($cacheControllerFactory))
			{
				throw new RuntimeException('Cannot get Joomla 4 cache controller factory');
			}

			/** @var CallbackController $cache */
			$cache = $cacheControllerFactory->createCacheController('callback', $options);

			if (empty($cache) || !property_exists($cache, 'cache') || !method_exists($cache->cache, 'clean'))
			{
				throw new RuntimeException('Cannot get Joomla 4 cache controller');
			}

			$cache->cache->clean();
		}
		catch (Throwable $e)
		{
			$options['result'] = false;
		}

		return $options;
	}

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @param   string  $event  The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data   A hash array of data sent to the plugins as part of the trigger
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 */
	public static function runPlugins(string $event, array $data = []): array
	{
		// If there's no JEventDispatcher try getting JApplication
		try
		{
			$app = JoomlaFactory::getApplication();
		}
		catch (Exception $e)
		{
			// If I can't get JApplication I cannot run the plugins.
			return [];
		}

		// Joomla 3 and 4 have triggerEvent
		if (method_exists($app, 'triggerEvent'))
		{
			return $app->triggerEvent($event, $data);
		}

		// Joomla 5 (and possibly some 4.x versions) don't have triggerEvent. Go through the Events dispatcher.
		if (method_exists($app, 'getDispatcher') && class_exists('Joomla\Event\Event'))
		{
			try
			{
				$dispatcher = $app->getDispatcher();
			}
			catch (UnexpectedValueException $exception)
			{
				return [];
			}

			if ($data instanceof Event)
			{
				$eventObject = $data;
			}
			elseif (is_array($data))
			{
				$eventObject = new Event($event, $data);
			}
			else
			{
				throw new InvalidArgumentException('The plugin data must either be an event or an array');
			}

			$result = $dispatcher->dispatch($event, $eventObject);

			return !isset($result['result']) || is_null($result['result']) ? [] : $result['result'];
		}

		// No viable way to run the plugins :(
		return [];
	}

	private static function getAppConfigParam(?object $app, string $key, $default = null)
	{
		/**
		 * Any kind of Joomla CMS, Web, API or CLI application extends from AbstractApplication and has the get()
		 * method to return application configuration parameters.
		 */
		if (is_object($app) && ($app instanceof AbstractApplication))
		{
			return $app->get($key, $default);
		}

		/**
		 * A custom application may instead implement the Joomla\Application\ConfigurationAwareApplicationInterface
		 * interface (Joomla 4+), in whihc case it has the get() method to return application configuration parameters.
		 */
		if (is_object($app)
			&& interface_exists('Joomla\Application\ConfigurationAwareApplicationInterface', true)
			&& ($app instanceof ConfigurationAwareApplicationInterface))
		{
			return $app->get($key, $default);
		}

		/**
		 * A Joomla 3 custom application may simply implement the get() method without implementing an interface.
		 */
		if (is_object($app) && method_exists($app, 'get'))
		{
			return $app->get($key, $default);
		}

		/**
		 * At this point the $app variable is not an object or is something I can't use. Does the Joomla Factory still
		 * has the legacy static method getConfig() to get the application configuration? If so, use it.
		 */
		if (method_exists(Factory::class, 'getConfig'))
		{
			try
			{
				$jConfig = Factory::getConfig();

				if (is_object($jConfig) && ($jConfig instanceof Registry))
				{
					$jConfig->get($key, $default);
				}
			}
			catch (Throwable $e)
			{
				/**
				 * Factory tries to go through the application object. It might fail if there is a custom application
				 * which doesn't implement the interfaces Factory expects. In this case we get a Fatal Error whcih we
				 * can trap and fall through to the next if-block.
				 */
			}
		}

		/**
		 * When we are here all hope is nearly lost. We have to do a crude approximation of Joomla Factory's code to
		 * create an application configuration Registry object and retrieve the configuration values. This will work as
		 * long as the JConfig class (defined in configuration.php) has been loaded.
		 */
		$configPath = defined('JPATH_CONFIGURATION') ? JPATH_CONFIGURATION :
			(defined('JPATH_ROOT') ? JPATH_ROOT : null);
		$configPath = $configPath ?? (__DIR__ . '/../../..');
		$configFile = $configPath . '/configuration.php';

		if (!class_exists('JConfig') && @file_exists($configFile) && @is_file($configFile) && @is_readable($configFile))
		{
			require_once $configFile;
		}

		if (class_exists('JConfig'))
		{
			try
			{
				$jConfig      = new Registry();
				$configObject = new JConfig();
				$jConfig->loadObject($configObject);

				return $jConfig->get($key, $default);
			}
			catch (Throwable $e)
			{
				return $default;
			}
		}

		/**
		 * All hope is lost. I can't find the application configuration. I am returning the default value and hope stuff
		 * won't break spectacularly...
		 */
		return $default;
	}

}