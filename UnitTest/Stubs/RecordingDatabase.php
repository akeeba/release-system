<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Stubs;

defined('_JEXEC') or die;

/**
 * A minimal stand-in for a Joomla database driver, sufficient for list-model `getListQuery()` tests.
 *
 * Identifier and value quoting return simple, predictable strings, so an assertion can look for a
 * back-ticked column name inside an `ORDER BY` expression and mean something. Every query handed to
 * `setQuery()` is retained.
 *
 * **It deliberately does NOT declare `createQuery()`.** ARS probes for that method
 * (`method_exists($db, 'createQuery') ? … : getQuery(true)`) because it only exists from Joomla 5.1
 * onwards, and the repository CLAUDE.md is explicit that those version branches are deliberate and
 * must not be simplified away. This class therefore exercises the older branch; use
 * {@see ModernRecordingDatabase} to exercise the newer one. A test that cares about the query a
 * model builds should run against both, because that is the only way to find out that the two
 * branches still agree.
 *
 * It extends the stub {@see \Joomla\Database\DatabaseDriver} so it satisfies every `DatabaseDriver $db`
 * constructor typehint in ARS's own Table classes (e.g. `new ReleaseTable($this->getDbo())`) — without
 * that, handing a Table class this double would fail with a TypeError before a test got anywhere near
 * the behaviour it wants to exercise.
 */
class RecordingDatabase extends \Joomla\Database\DatabaseDriver
{
	/** @var RecordingQuery|null The most recent query handed to setQuery() */
	public $lastQuery;

	/** @var RecordingQuery[] Every query handed to setQuery(), in order */
	public $queries = [];

	/**
	 * Every query OBJECT created via getQuery()/createQuery(), in creation order — including ones never handed to
	 * setQuery(), e.g. a sub-select built purely for string concatenation. A model that embeds a sub-query via
	 * `(string) $subQuery` never calls setQuery() on it, so {@see $queries} would never see it; a test that needs to
	 * inspect that sub-query's own where()/whereIn() calls reads it from here instead, by creation order.
	 *
	 * @var RecordingQuery[]
	 */
	public $createdQueries = [];

	/** @var mixed What loadObjectList()/loadObject()/loadResult()/loadAssoc()/loadAssocList() return by default. Assign per test. */
	public $result;

	public function getQuery($new = true)
	{
		$query                  = new RecordingQuery();
		$this->createdQueries[] = $query;

		return $query;
	}

	public function setQuery($query, $offset = 0, $limit = 0)
	{
		$this->lastQuery = $query;
		$this->queries[] = $query;

		return $this;
	}

	public function execute()
	{
		return true;
	}

	public function loadObjectList($key = '', $class = \stdClass::class)
	{
		return \is_array($this->result) ? $this->result : [];
	}

	public function loadObject($class = \stdClass::class)
	{
		return \is_array($this->result) ? ($this->result[0] ?? null) : $this->result;
	}

	public function loadResult()
	{
		return $this->result;
	}

	public function loadColumn($offset = 0)
	{
		return \is_array($this->result) ? $this->result : [];
	}

	/**
	 * Mimics Joomla's loadAssocList(): with no $key, every row as a plain array; with a $key, the rows indexed by
	 * that column's value — the whole row, or just $column's value when given. Rows may be handed to a test as
	 * arrays or objects; both are normalised the same way real Joomla does.
	 */
	public function loadAssocList($key = '', $column = null)
	{
		$rows = \is_array($this->result) ? $this->result : [];
		$rows = array_map(static fn($row) => (array) $row, $rows);

		if ($key === '')
		{
			return $rows;
		}

		$out = [];

		foreach ($rows as $row)
		{
			$out[$row[$key] ?? null] = $column !== null ? ($row[$column] ?? null) : $row;
		}

		return $out;
	}

	/**
	 * Mimics Joomla's loadAssoc(): the first row as a plain array, or NULL when there are none. This single-shot
	 * form always answers from the FIRST row of {@see $result}; a test driving a collision-retry loop (repeated
	 * loadAssoc() calls that must return different things each time) needs
	 * {@see \Akeeba\ARS\UnitTest\Stubs\ScriptedRecordingDatabase} instead.
	 */
	public function loadAssoc()
	{
		$rows = \is_array($this->result) ? $this->result : [];

		return isset($rows[0]) ? (array) $rows[0] : null;
	}

	public function quoteName($name, $as = null)
	{
		if (\is_array($name))
		{
			return array_map(fn($single) => $this->quoteName($single), $name);
		}

		return '`' . $name . '`' . ($as !== null ? ' AS `' . $as . '`' : '');
	}

	public function qn($name, $as = null)
	{
		return $this->quoteName($name, $as);
	}

	public function quote($text, $escape = true)
	{
		if (\is_array($text))
		{
			return array_map(fn($single) => $this->quote($single), $text);
		}

		return "'" . ($escape ? $this->escape((string) $text) : $text) . "'";
	}

	public function q($text, $escape = true)
	{
		return $this->quote($text, $escape);
	}

	public function escape($text, $extra = false)
	{
		return addslashes((string) $text);
	}

	public function getPrefix()
	{
		return 'e2e_';
	}

	public function replacePrefix($sql, $prefix = '#__')
	{
		return str_replace($prefix, $this->getPrefix(), (string) $sql);
	}
}
