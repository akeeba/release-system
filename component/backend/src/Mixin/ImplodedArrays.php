<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

/**
 * Trait for dealing with imploded arrays, stored as comma-separated values
 */
trait ImplodedArrays
{
	/**
	 * Converts the loaded comma-separated list into an array
	 *
	 * @param   string|array  $value  The comma-separated list
	 * @param   bool          $trim   Should I trim the array results after exploding the array?
	 *
	 * @return  array  The exploded array
	 */
	protected function stringToArray($value, bool $trim = true): array
	{
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return [];
		}

		$value = explode(',', $value) ?: [];

		return $trim ? array_map('trim', $value) : $value;
	}

	/**
	 * Converts an array of values into a comma separated list
	 *
	 * @param   array|string  $value  The array of values (or the already imploded array as a string)
	 * @param   bool          $trim   Should I trim() the values of the array before imploding?
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function arrayToString($value, bool $trim = true): string
	{
		if (is_string($value))
		{
			return $value;
		}

		if (!is_array($value))
		{
			return '';
		}

		if ($trim)
		{
			$value = array_map('trim', $value);
		}

		return implode(',', $value);
	}
}
