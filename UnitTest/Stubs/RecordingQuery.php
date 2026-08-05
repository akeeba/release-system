<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Stubs;

defined('_JEXEC') or die;

/**
 * A fluent, no-op stand-in for Joomla\Database\DatabaseQuery.
 *
 * It builds no SQL. Instead it records the arguments handed to the clause builders a model calls —
 * `order()`, `where()`, `whereIn()`, `bind()` — so a test can assert exactly which `ORDER BY`
 * expression, which conditions and which bound parameters `getListQuery()` emitted for a given
 * filter state, with no database anywhere.
 *
 * That is the right shape for the question these tests exist to answer. ARS's list models take
 * ordering columns and directions from request state; the defence is that the column is passed
 * through `quoteName()` and the direction is whitelisted. Both are observable here, and neither
 * needs a real database to observe.
 */
class RecordingQuery
{
	/** @var string[] Every expression passed to order() */
	public $orderCalls = [];

	/** @var string[] Every condition passed to where() */
	public $whereCalls = [];

	/** @var array<string,mixed> Bound parameter name => value, as passed to bind() */
	public $bindValues = [];

	/** @var array<string,int|string|null> Bound parameter name => declared data type */
	public $bindTypes = [];

	/** @var array<int,array{0:string,1:mixed,2:mixed}> Column, values and data type passed to whereIn() */
	public $whereInCalls = [];

	/** @var string[] Every table passed to from() */
	public $fromCalls = [];

	/** @var array<int,array{0:string,1:mixed}> Join type and clause passed to any join method */
	public $joinCalls = [];

	public function select($columns)
	{
		return $this;
	}

	public function from($table, $alias = null)
	{
		foreach ((array) $table as $single)
		{
			$this->fromCalls[] = (string) $single;
		}

		return $this;
	}

	public function join($type, $table, $condition = null)
	{
		$this->joinCalls[] = [(string) $type, $table];

		return $this;
	}

	public function leftJoin($table, $condition = null)
	{
		return $this->join('LEFT', $table, $condition);
	}

	public function innerJoin($table, $condition = null)
	{
		return $this->join('INNER', $table, $condition);
	}

	public function rightJoin($table, $condition = null)
	{
		return $this->join('RIGHT', $table, $condition);
	}

	public function outerJoin($table, $condition = null)
	{
		return $this->join('OUTER', $table, $condition);
	}

	public function group($columns)
	{
		return $this;
	}

	public function having($conditions, $glue = 'AND')
	{
		return $this;
	}

	public function where($condition, $glue = 'AND')
	{
		foreach ((array) $condition as $single)
		{
			$this->whereCalls[] = (string) $single;
		}

		return $this;
	}

	public function extendWhere($outerGlue, $conditions, $innerGlue = 'AND')
	{
		return $this->where($conditions, $innerGlue);
	}

	public function whereIn($column, $values, $dataType = null)
	{
		$this->whereInCalls[] = [(string) $column, $values, $dataType];

		return $this;
	}

	public function whereNotIn($column, $values, $dataType = null)
	{
		$this->whereInCalls[] = [(string) $column, $values, $dataType];

		return $this;
	}

	public function bind($key, $value = null, $dataType = null)
	{
		foreach ((array) $key as $index => $singleKey)
		{
			$this->bindValues[$singleKey] = \is_array($value) ? ($value[$index] ?? null) : $value;
			$this->bindTypes[$singleKey]  = \is_array($dataType) ? ($dataType[$index] ?? null) : $dataType;
		}

		return $this;
	}

	/**
	 * Removes a previously bind()-ed parameter, matching Joomla's DatabaseQuery::unbind() — used by
	 * ModelCopyTrait::generateNewTitle() to re-bind ':alias' to a new value on each collision-retry iteration.
	 */
	public function unbind($key)
	{
		foreach ((array) $key as $singleKey)
		{
			unset($this->bindValues[$singleKey], $this->bindTypes[$singleKey]);
		}

		return $this;
	}

	public function order($columns)
	{
		foreach ((array) $columns as $single)
		{
			$this->orderCalls[] = (string) $single;
		}

		return $this;
	}

	public function setLimit($limit = 0, $offset = 0)
	{
		return $this;
	}

	// Non-SELECT verbs some models touch elsewhere; harmless no-ops kept for completeness.

	public function delete($table)
	{
		return $this;
	}

	public function update($table)
	{
		return $this;
	}

	public function set($conditions, $glue = ',')
	{
		return $this;
	}

	public function insert($table, $incrementField = false)
	{
		return $this;
	}

	public function columns($columns)
	{
		return $this;
	}

	public function values($values)
	{
		return $this;
	}

	/**
	 * The single ORDER BY expression the model emitted, or null when it emitted none.
	 *
	 * Convenience for the common assertion. When a model orders by more than one expression, read
	 * {@see RecordingQuery::$orderCalls} directly rather than reaching for this.
	 */
	public function firstOrder(): ?string
	{
		return $this->orderCalls[0] ?? null;
	}

	/**
	 * Allows a query to be embedded as a sub-select via string concatenation, e.g. "($sub) AS x".
	 */
	public function __toString()
	{
		return '';
	}
}
