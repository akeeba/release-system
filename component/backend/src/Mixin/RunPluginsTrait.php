<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Application\AfterInitialiseDocumentEvent;
use Joomla\CMS\Event\Application\DaemonForkEvent;
use Joomla\CMS\Event\Application\DaemonReceiveSignalEvent;
use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use Joomla\CMS\Event\CoreEventAware;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Event\Editor\EditorSetupEvent;
use Joomla\CMS\Event\Model\AfterCleanCacheEvent;
use Joomla\CMS\Event\MultiFactor\BeforeDisplayMethods;
use Joomla\CMS\Event\MultiFactor\Callback;
use Joomla\CMS\Event\MultiFactor\Captive;
use Joomla\CMS\Event\MultiFactor\GetMethod;
use Joomla\CMS\Event\MultiFactor\GetSetup;
use Joomla\CMS\Event\MultiFactor\NotifyActionLog;
use Joomla\CMS\Event\MultiFactor\SaveSetup;
use Joomla\CMS\Event\MultiFactor\Validate;
use Joomla\CMS\Event\WebAsset\WebAssetRegistryAssetChanged;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Psr\Log\LogLevel;

/**
 * A trait to easily run plugin events.
 *
 * This trait builds on my work in the Joomla! core itself. The trait has both static and non-static methods.
 *
 * @copyright Copyright (C) 2010-2024 Akeeba Ltd
 * @license   GPL3
 * @since     2023.08.25
 */
trait RunPluginsTrait
{
	use CoreEventAware;

	/**
	 * Custom event mapping for extension-specific plugin events.
	 *
	 * @var   array
	 * @since 2023.08.25
	 */
	protected static array $akeebaRunPluginsCustomMap = [];

	/**
	 * Get the concrete event class name for the given event name.
	 *
	 * This method falls back to the generic Joomla\Event\Event class if the event name is unknown to this trait.
	 *
	 * @param   string  $eventName  The event name
	 *
	 * @return  string The event class name
	 * @since   2023.08.25
	 * @internal
	 */
	protected static function getEventClassByEventNameAugmentedByAkeeba(string $eventName): string
	{
		/**
		 * Our event map has three sources:
		 *
		 * 1. The $akeebaRunPluginsCustomMap static variable for extension-specific events
		 * 2. Our hotfix for core events missing from Joomla's CoreEventAware trait
		 * 3. Joomla's CoreEventAware trait itself (the $eventNameToConcreteClass static variable)
		 *
		 * This allows us to gracefully extend the scope of the core Joomla CoreEventAware trait which I had contributed
		 * a few years ago, and the upkeep of which appears to be hit-and-miss by the core mainteners.
		 */
		$eventMap = array_merge(
			self::$akeebaRunPluginsCustomMap,
			[
				// Application
				'onAfterInitialiseDocument'                            => AfterInitialiseDocumentEvent::class,
				'onFork'                                               => DaemonForkEvent::class,
				'onReceiveSignal'                                      => DaemonReceiveSignalEvent::class,

				// CAPTCHA
				'onCaptchaSetup'                                       => CaptchaSetupEvent::class,

				// Editors
				'onEditorButtonsSetup'                                 => EditorButtonsSetupEvent::class,
				'onEditorSetup'                                        => EditorSetupEvent::class,

				// Cache clean
				'onContentCleanCache'                                  => AfterCleanCacheEvent::class,

				// MFA
				'onUserMultifactorBeforeDisplayMethods'                => BeforeDisplayMethods::class,
				'onUserMultifactorCallback'                            => Callback::class,
				'onUserMultifactorCaptive'                             => Captive::class,
				'onUserMultifactorGetMethod'                           => GetMethod::class,
				'onUserMultifactorGetSetup'                            => GetSetup::class,
				'onComUsersViewMethodsAfterDisplay'                    => NotifyActionLog::class,
				'onComUsersCaptiveShowCaptive'                         => NotifyActionLog::class,
				'onComUsersCaptiveShowSelect'                          => NotifyActionLog::class,
				'onComUsersCaptiveValidateFailed'                      => NotifyActionLog::class,
				'onComUsersCaptiveValidateInvalidMethod'               => NotifyActionLog::class,
				'onComUsersCaptiveValidateTryLimitReached'             => NotifyActionLog::class,
				'onComUsersCaptiveValidateSuccess'                     => NotifyActionLog::class,
				'onComUsersControllerMethodAfterRegenerateBackupCodes' => NotifyActionLog::class,
				'onComUsersControllerMethodBeforeAdd'                  => NotifyActionLog::class,
				'onComUsersControllerMethodBeforeDelete'               => NotifyActionLog::class,
				'onComUsersControllerMethodBeforeEdit'                 => NotifyActionLog::class,
				'onComUsersControllerMethodBeforeSave'                 => NotifyActionLog::class,
				'onComUsersControllerMethodsBeforeDisable'             => NotifyActionLog::class,
				'onComUsersControllerMethodsBeforeDoNotShowThisAgain'  => NotifyActionLog::class,
				'onUserMultifactorSaveSetup'                           => SaveSetup::class,
				'onUserMultifactorValidate'                            => Validate::class,

				// Web Asset Manager
				'onWebAssetRegistryChangedAsset'                       => WebAssetRegistryAssetChanged::class,

			],
			self::$eventNameToConcreteClass
		);

		if (!isset($eventMap[$eventName]))
		{
			return Event::class;
		}

		$class = $eventMap[$eventName];

		if (class_exists($class))
		{
			return $class;
		}

		return Event::class;
	}

	/**
	 * Execute a plugin event and return its results. Static version, to be used by Helpers.
	 *
	 * @param   string       $event               The event name
	 * @param   array        $arguments           The event arguments
	 * @param   string|null  $className           The concrete event's class name; null to have Joomla auto-detect it.
	 * @param   mixed        $dispatcherOrSource  A DispatcherInterface or DispatcherAwareInterface (e.g. Application)
	 *                                            object.
	 *
	 * @return  array
	 * @since   2023.08.25
	 */
	protected static function triggerPluginEventStatic(string $event, array $arguments, ?string $className = null, $dispatcherOrSource = null): array
	{
		$shouldLog = JDEBUG && empty($className);

		// If the $dispatcherOrSource parameter is a dispatcher we'll use it.
		$dispatcher = ($dispatcherOrSource instanceof DispatcherInterface) ? $dispatcherOrSource : null;

		// If we don't have a dispatcher, we need to go through a DispatcherAwareInterface object.
		if (empty($dispatcher))
		{
			try
			{
				// If we're not given a DispatcherAwareInterface object we'll try to go through the application object
				$dispatcherAware = ($dispatcherOrSource instanceof DispatcherAwareInterface)
					? $dispatcherOrSource
					: Factory::getApplication();
				// Hopefully this yields a dispatcher object.
				$dispatcher = $dispatcherAware->getDispatcher();
			}
			catch (\Throwable $e)
			{
				return [];
			}
		}

		$className = $className ?: self::getEventClassByEventNameAugmentedByAkeeba($event);

		if ($shouldLog)
		{
			self::logTriggerPluginEventWithoutClass($event, $className);
		}

		$eventObject = new $className($event, $arguments);
		$eventResult = $dispatcher->dispatch($event, $eventObject);
		$results     = $eventResult->getArgument('result') ?: [];

		return is_array($results) ? $results : [];
	}

	/**
	 * Execute a plugin event and return its results. Normal version, to be used by concrete objects.
	 *
	 * @param   string       $event                The event name
	 * @param   array        $arguments            The event arguments
	 * @param   string|null  $className            The concrete event's class name; null to have Joomla auto-detect it.
	 * @param   mixed        $dispatcherOrSource   A DispatcherInterface or DispatcherAwareInterface (e.g. Application)
	 *                                             object.
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   2023.08.25
	 */
	protected function triggerPluginEvent(string $event, array $arguments, ?string $className = null, $dispatcherOrSource = null): array
	{
		// If I am given a DispatcherInterface object, or a DispatcherAwareInterface (e.g. Application) object return fast.
		if ($dispatcherOrSource instanceof DispatcherAwareInterface || $dispatcherOrSource instanceof DispatcherInterface)
		{
			return self::triggerPluginEventStatic($event, $arguments, $className, $dispatcherOrSource);
		}

		// If we are a DispatcherAwareInterface object ourselves return fast.
		$dispatcher = $this instanceof DispatcherAwareInterface ? $this->getDispatcher() : null;

		if (!is_null($dispatcher))
		{
			return self::triggerPluginEventStatic($event, $arguments, $className, $dispatcher);
		}

		/**
		 * Since we're not given a dispatcher or dispatcher aware object, and we're not a dispatcher aware obejct
		 * ourselves, we need to get the Joomla! Application object as the most likely candidate of the dispatcher
		 * aware object we should be using.
		 */
		if (method_exists($this, 'getApplication'))
		{
			$app = $this->getApplication();
		}
		elseif (property_exists($this, 'app') && $this->app instanceof CMSApplication)
		{
			$app = $this->app;
		}
		else
		{
			$app = Factory::getApplication();
		}

		if (!$app instanceof DispatcherAwareInterface)
		{
			return [];
		}

		return self::triggerPluginEventStatic($event, $arguments, $className, $dispatcher);
	}

	private static function logTriggerPluginEventWithoutClass(string $event, string $className)
	{
		// Register a log file
		static $hasLogFile = false;

		if (!$hasLogFile)
		{
			Log::addLogger([
				'text_file'         => 'akeeba_runPluginsTrait.php',
				'text_entry_format' => '{DATETIME}	{MESSAGE}',
				'defer'             => true,
			], Log::ALL, ['akeeba.runPluginsTrait']);
		}

		// Get the caller using debug backtrace. REMEMBER: STATIC CALLS GO ONE LEVEL DEEPER!
		$callers    = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
		$callTarget = ($callers[2]['function'] === 'triggerPluginEventStatic') ? $callers[3] : $callers[2];

		Log::add(
			sprintf(
				'Event "%s" resolved to class %s -- called from %s%s%s at %s line %d',
				$event,
				$className,
				$callTarget['class'],
				$callTarget['type'],
				$callTarget['function'],
				$callTarget['file'],
				$callTarget['line']
			),
			LogLevel::DEBUG,
			'akeeba.runPluginsTrait'
		);
	}
}