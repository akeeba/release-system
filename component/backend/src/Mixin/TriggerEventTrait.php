<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

trait TriggerEventTrait
{
	/**
	 * Triggers an object-specific event. The event runs both locally –if a suitable method exists– and through the
	 * Joomla! plugin system. A true/false return value is expected. The first false return cancels the event.
	 *
	 * EXAMPLE
	 * Component: com_foobar, Object name: item, Event: onBeforeSomething, Arguments: array(123, 456)
	 * The event calls:
	 * 1. $this->onBeforeSomething(123, 456)
	 * 2. $this->checkACL('@something') if there is no onBeforeSomething and the event starts with onBefore
	 * 3. Joomla! plugin event onComFoobarControllerItemBeforeSomething($this, 123, 456)
	 *
	 * @param   string  $event      The name of the event, typically named onPredicateVerb e.g. onBeforeKick
	 * @param   array   $arguments  The arguments to pass to the event handlers
	 *
	 * @return  bool
	 */
	protected function triggerEvent(string $event, array $arguments = []): bool
	{
		// If there is an object method for this event, call it
		if (method_exists($this, $event))
		{
			/**
			 * IMPORTANT! We use call_user_func_array() so we can pass arguments by reference.
			 */
			if (call_user_func_array([$this, $event], $arguments) === false)
			{
				return false;
			}
		}

		// All other event handlers live outside this object, therefore they need to be passed a reference to this
		// object as the first argument.
		array_unshift($arguments, $this);

		// If we have an "on" prefix for the event (e.g. onFooBar) remove it and stash it for later.
		$prefix = '';

		if (substr($event, 0, 2) == 'on')
		{
			$prefix = 'on';
			$event  = substr($event, 2);
		}

		// Get the component name and object type from the namespace of the caller
		$callers        = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
		$namespaceParts = explode('\\', $callers[1]['class']);
		$className      = array_pop($namespaceParts);
		$objectType     = array_pop($namespaceParts);
		array_pop($namespaceParts);
		$bareComponent = strtolower(array_pop($namespaceParts));

		// Get the component/model prefix for the event
		$prefix .= 'Com' . ucfirst($bareComponent);
		$prefix .= ucfirst($className);

		// The event name will be something like onComFoobarControllerItemsBeforeSomething
		$event = $prefix . $event;

		// Call the Joomla! plugins
		$results = Factory::getApplication()->triggerEvent($event, $arguments);

		return !in_array(false, $results, true);
	}

}