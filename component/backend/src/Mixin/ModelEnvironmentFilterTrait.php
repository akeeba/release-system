<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Adds a "contains this environment ID" filter to a list model.
 *
 * @since  7.5.1
 */
trait ModelEnvironmentFilterTrait
{
	/**
	 * Restrict a query to the records whose environments column includes the given environment ID.
	 *
	 * The environments column holds a JSON array of numeric IDs, e.g. `["1","4"]`. Records written by
	 * much older versions of the component may instead hold a bare comma–separated list, e.g. `1,4`.
	 * Stripping the JSON punctuation with REPLACE() reduces both formats to the same comma–separated
	 * list, which we can then match with four LIKE comparisons — one per position the ID can occupy.
	 * We do it this way, rather than with a JSON function or by concatenating delimiters around the
	 * column, because the component has to run on both MySQL and PostgreSQL and neither JSON_CONTAINS
	 * nor CONCAT() is portable between the two.
	 *
	 * @param   QueryInterface  $query          The query to restrict.
	 * @param   string          $column         The (table prefixed) name of the environments column.
	 * @param   int             $environmentId  The environment ID to look for.
	 *
	 * @return  void
	 * @since   7.5.1
	 */
	/**
	 * Normalise a raw environments column value into an array of environment IDs.
	 *
	 * The column may hold a JSON array, a legacy comma–separated list, an empty string or NULL. Records loaded
	 * through a Table object have already been decoded by its onBeforeBind() handler; list models query the
	 * database directly and get the raw value, so they need this.
	 *
	 * @param   mixed  $value  The raw column value.
	 *
	 * @return  int[]  The environment IDs.
	 * @since   7.5.1
	 */
	protected function normaliseEnvironments($value): array
	{
		if (is_array($value))
		{
			return ArrayHelper::toInteger($value);
		}

		if (is_object($value))
		{
			return ArrayHelper::toInteger(array_values((array) $value));
		}

		$value = trim((string) ($value ?? ''));

		if ($value === '')
		{
			return [];
		}

		$decoded = @json_decode($value, true);

		if (!is_array($decoded))
		{
			$decoded = explode(',', $value);
		}

		return ArrayHelper::toInteger(array_values(array_filter($decoded, fn($x) => trim((string) $x) !== '')));
	}

	protected function applyEnvironmentFilter(QueryInterface $query, string $column, int $environmentId): void
	{
		$db = $this->getDatabase();

		$normalised = 'REPLACE(REPLACE(REPLACE(REPLACE(' . $db->quoteName($column)
			. ", '[', ''), ']', ''), '\"', ''), ' ', '')";

		/**
		 * Each placeholder gets its own variable. QueryInterface::bind() takes its value BY REFERENCE and
		 * holds on to it until the query is executed, so re-using one variable would make every
		 * placeholder resolve to the last value assigned to it.
		 */
		$envOnly   = (string) $environmentId;
		$envFirst  = $environmentId . ',%';
		$envMiddle = '%,' . $environmentId . ',%';
		$envLast   = '%,' . $environmentId;

		$query->extendWhere(
			'AND',
			[
				$normalised . ' = :envOnly',
				$normalised . ' LIKE :envFirst',
				$normalised . ' LIKE :envMiddle',
				$normalised . ' LIKE :envLast',
			],
			'OR'
		)
			->bind(':envOnly', $envOnly, ParameterType::STRING)
			->bind(':envFirst', $envFirst, ParameterType::STRING)
			->bind(':envMiddle', $envMiddle, ParameterType::STRING)
			->bind(':envLast', $envLast, ParameterType::STRING);
	}
}
