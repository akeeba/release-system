<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TriggerEventTrait;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

#[\AllowDynamicProperties]
abstract class AbstractTable extends Table
{
	use TriggerEventTrait;

	public function __construct($table, $key, DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct($table, $key, $db, $dispatcher);

		$this->triggerEvent('onAfterInitialise');
	}

	public function reset()
	{
		$this->triggerEvent('onBeforeReset');

		parent::reset();

		$this->triggerEvent('onAfterReset');
	}

	public function bind($src, $ignore = [])
	{
		$this->triggerEvent('onBeforeBind', [&$src, &$ignore]);

		$result = parent::bind($src, $ignore);

		$this->triggerEvent('onAfterBind', [&$result, $src, $ignore]);

		return $result;
	}

	public function load($keys = null, $reset = true)
	{
		$this->triggerEvent('onBeforeLoad', [&$keys, &$reset]);

		$result = parent::load($keys, $reset);

		$this->triggerEvent('onAfterLoad', [&$result, $keys, $reset]);

		return $result;
	}

	public function check()
	{
		$this->triggerEvent('onBeforeCheck');

		$result = parent::check();

		$this->triggerEvent('onAfterCheck', [&$result]);

		return $result;
	}

	public function store($updateNulls = false)
	{
		$this->triggerEvent('onBeforeStore', [&$updateNulls]);

		$result = parent::store($updateNulls);

		$this->triggerEvent('onAfterStore', [&$result, $updateNulls]);

		return $result;
	}

	public function save($src, $orderingFilter = '', $ignore = '')
	{
		$this->triggerEvent('onBeforeSave', [&$src, &$orderingFilter, &$ignore]);

		$result = parent::save($src, $orderingFilter, $ignore);

		$this->triggerEvent('onAfterSave', [&$result, $src, $orderingFilter, $ignore]);

		return $result;
	}

	public function delete($pk = null)
	{
		$this->triggerEvent('onBeforeDelete', [&$pk]);

		$result = parent::delete($pk);

		$this->triggerEvent('onAfterDelete', [&$result, $pk]);

		return $result;
	}

	public function checkOut($userId, $pk = null)
	{
		$this->triggerEvent('onBeforeCheckout', [&$userId, &$pk]);

		$result = parent::checkOut($userId, $pk);

		$this->triggerEvent('onAfterCheckout', [&$result, $userId, $pk]);

		return $result;
	}

	public function checkIn($pk = null)
	{
		$this->triggerEvent('onBeforeCheckIn', [&$pk]);

		$result = parent::checkIn($pk);

		$this->triggerEvent('onAfterCheckIn', [&$result, $pk]);

		return $result;
	}

	public function hit($pk = null)
	{
		$this->triggerEvent('onBeforeHit', [&$pk]);

		$result = parent::hit($pk);

		$this->triggerEvent('onAfterHit', [&$result, $pk]);

		return $result;
	}

	public function reorder($where = '')
	{
		$this->triggerEvent('onBeforeReorder', [&$where]);

		$result = parent::reorder($where);

		$this->triggerEvent('onAfterReorder', [&$result, $where]);

		return $result;
	}

	public function move($delta, $where = '')
	{
		$this->triggerEvent('onBeforeMove', [&$delta, &$where]);

		$result = parent::move($delta, $where);

		$this->triggerEvent('onAfterMove', [&$result, $delta, $where]);

		return $result;
	}

	public function publish($pks = null, $state = 1, $userId = 0)
	{
		$this->triggerEvent('onBeforePublish', [&$pks, &$state, &$userId]);

		$result = parent::publish($pks, $state, $userId);

		$this->triggerEvent('onAfterPublish', [&$result, $pks, $state, $userId]);

		return $result;
	}

	public function getAssetName()
	{
		return $this->_getAssetName();
	}
}