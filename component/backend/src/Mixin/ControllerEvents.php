<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use RuntimeException;

trait ControllerEvents
{
	use TriggerEvent;

	/**
	 * Execute a task by triggering a method in the derived class.
	 *
	 * Overridden to apply a custom ACL check and trigger before/after methods.
	 *
	 * @param   string  $task  The task to perform. If no matching task is found, the '__default' task is executed, if
	 *                         defined.
	 *
	 * @return  mixed   The value returned by the called method.
	 *
	 * @throws  \Exception
	 * @since   9.0.0
	 */
	public function execute($task)
	{
		$this->task = $task;

		$task = strtolower($task);

		if (isset($this->taskMap[$task]))
		{
			$doTask = $this->taskMap[$task];
		}
		elseif (isset($this->taskMap['__default']))
		{
			$doTask = $this->taskMap['__default'];
		}
		else
		{
			throw new RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_TASK_NOT_FOUND', $task), 404);
		}

		// Execute onBeforeExecute and onBefore<Task> events
		$eventName = 'onBefore' . ucfirst($task);

		$this->triggerEvent('onBeforeExecute', [&$task]);
		$this->triggerEvent($eventName);

		// The task may have changed, so let's try that once again.
		if (isset($this->taskMap[$task]))
		{
			$doTask = $this->taskMap[$task];
		}
		elseif (isset($this->taskMap['__default']))
		{
			$doTask = $this->taskMap['__default'];
		}
		else
		{
			throw new RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_TASK_NOT_FOUND', $task), 404);
		}

		// Record the actual task being fired and execute it.
		$this->doTask = $doTask;
		$result       = $this->$doTask();

		// Execute onAfter<Task> and onAfterExecute events
		$eventName = 'onAfter' . ucfirst($task);

		$this->triggerEvent($eventName);
		$this->triggerEvent('onAfterExecute', [$task]);

		return $result;
	}

}