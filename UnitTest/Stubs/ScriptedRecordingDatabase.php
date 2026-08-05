<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Stubs;

defined('_JEXEC') or die;

/**
 * A {@see RecordingDatabase} whose read methods can be scripted per test, for the two shapes a table or model under
 * test commonly needs and {@see RecordingDatabase}'s single shared `$result` property cannot express:
 *
 * 1. **Different queries in the same method need different answers.** `ItemTable::onBeforeCheck()` alone issues a
 *    title/alias uniqueness query, then (via `applyAutoDescriptions()`) an autodescription-matching query, then (via
 *    `getUpdateStream()`) an update-stream-matching query — all against the same `$db`. {@see $byTable} answers each
 *    by inspecting which table the query's `from()` targeted, so a test can hand each its own fixture without the
 *    three interfering with each other.
 * 2. **A collision-retry loop needs a different answer on each successive call**, e.g.
 *    `DlidlabelTable::createNewDownloadID()`'s `while (true)` loop or `ModelCopyTrait::generateNewTitle()`'s
 *    while-loadAssoc loop. {@see $resultQueue} and {@see $assocQueue} are shifted one value per call, so a test can
 *    script "collide twice, then succeed" precisely.
 */
class ScriptedRecordingDatabase extends RecordingDatabase
{
	/**
	 * Substring of the table name passed to from() => the RAW ROWS (same shape `$result` would hold: a list of
	 * arrays or objects) that query should answer with. Checked against {@see RecordingQuery::$fromCalls} of the
	 * query just handed to setQuery().
	 *
	 * Rows are routed through the parent class's normal loadObjectList()/loadAssocList() logic (see below) rather
	 * than returned verbatim, so a `loadAssocList('title', 'alias')`-style call still gets keyed the way the real
	 * method would key it — only *which rows* it sees is scripted, not how they get shaped into a return value.
	 *
	 * @var array<string,mixed>
	 */
	public $byTable = [];

	/** @var array<int,mixed> Successive loadResult() return values, shifted off in call order once queued. */
	public $resultQueue = [];

	/** @var array<int,mixed> Successive loadAssoc() return values, shifted off in call order once queued. */
	public $assocQueue = [];

	public function loadResult()
	{
		if (!empty($this->resultQueue))
		{
			return array_shift($this->resultQueue);
		}

		return parent::loadResult();
	}

	public function loadAssoc()
	{
		if (!empty($this->assocQueue))
		{
			return array_shift($this->assocQueue);
		}

		return parent::loadAssoc();
	}

	public function loadObjectList($key = '', $class = \stdClass::class)
	{
		return $this->withScriptedResult(fn() => parent::loadObjectList($key, $class));
	}

	public function loadAssocList($key = '', $column = null)
	{
		return $this->withScriptedResult(fn() => parent::loadAssocList($key, $column));
	}

	/**
	 * Temporarily swaps {@see RecordingDatabase::$result} for the rows scripted for the table the most recent
	 * query targeted (if any), runs $delegate against it, then restores the original value — so scripting one
	 * table's answer never leaks into a query against a different table.
	 */
	private function withScriptedResult(callable $delegate)
	{
		$scripted = $this->scriptedForCurrentTable();

		if ($scripted === null)
		{
			return $delegate();
		}

		$saved        = $this->result;
		$this->result = $scripted;

		try
		{
			return $delegate();
		}
		finally
		{
			$this->result = $saved;
		}
	}

	/**
	 * @return array|null The scripted rows for the table the most recent query targeted, or NULL if none of
	 *                     {@see $byTable}'s keys matched (so the caller should fall back to the generic behaviour).
	 */
	private function scriptedForCurrentTable(): ?array
	{
		$from = $this->lastQuery->fromCalls[0] ?? '';

		foreach ($this->byTable as $needle => $value)
		{
			if ($needle !== '' && str_contains($from, $needle))
			{
				return $value;
			}
		}

		return null;
	}
}
