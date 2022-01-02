<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

trait TaskBasedEvents
{
	use TriggerEvent;

	public function display($tpl = null)
	{
		$task = $this->getModel()->getState('task');

		$eventName = 'onBefore' . ucfirst($task);
		$this->triggerEvent($eventName, [&$tpl]);

		parent::display($tpl);

		$eventName = 'onAfter' . ucfirst($task);
		$this->triggerEvent($eventName, [&$tpl]);
	}
}