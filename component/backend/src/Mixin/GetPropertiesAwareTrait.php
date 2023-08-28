<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

trait GetPropertiesAwareTrait
{
	/**
	 * Convert the object to an array.
	 *
	 * This is a **FAR** more efficient way to do things than the crap used by Joomla!. PHP always adds a NULL byte in
	 * front of private properties' names when casting an object to array. We exploit this quirk to filter out private
	 * properties without using the slow-as-molasses PHP Reflection.
	 *
	 * @param  bool  $public
	 *
	 * @return array
	 *
	 * @since  7.3.0
	 */
	public function getProperties($public = true)
	{
		$asArray = (array) $this;

		if (!$public)
		{
			return $asArray;
		}

		return array_filter($asArray, fn($x) => !empty($x) && !is_numeric($x) && ord(substr($x, 0, 1)) !== 0, ARRAY_FILTER_USE_KEY);
	}
}