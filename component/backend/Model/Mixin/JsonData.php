<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\Mixin;

defined('_JEXEC') or die;

/**
 * Trait for dealing with data stored as JSON-encoded strings
 */
trait JsonData
{
	/**
	 * Converts the loaded JSON string into an array
	 *
	 * @param   string  $value  The JSON string
	 *
	 * @return  array  The data
	 */
	protected function getAttributeForJson($value)
	{
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return array();
		}

		$value = json_decode($value, true);

		if (empty($value))
		{
			return array();
		}

		return $value;
	}

	/**
	 * Converts and array into a JSON string
	 *
	 * @param   array  $value  The data
	 *
	 * @return  string  The JSON string
	 */
	protected function setAttributeForJson($value)
	{
		if (!is_array($value))
		{
			return $value;
		}

		$value = json_encode($value);

		return $value;
	}
}