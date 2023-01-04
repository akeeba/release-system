<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use RuntimeException;

/**
 * Runtime assertions which, unlike PHP assertions, cannot be turned off.
 *
 * This is meant to throw RuntimeErrors whenever a configuration or table column value is provided that doesn't mean a
 * set of hard requirements. Typically used in a table's check() method.
 */
trait TableAssertionTrait
{
	/**
	 * Make sure $condition is true or throw a RuntimeException with the $message language string
	 *
	 * @param   bool    $condition  The condition which must be true
	 * @param   string  $message    The language key for the message to throw
	 *
	 * @throws  RuntimeException
	 */
	protected function assert($condition, $message)
	{
		if (!$condition)
		{
			throw new RuntimeException(Text::_($message));
		}
	}

	/**
	 * Assert that $value is not empty or throw a RuntimeException with the $message language string
	 *
	 * @param   mixed   $value    The value to check
	 * @param   string  $message  The language key for the message to throw
	 *
	 * @throws  RuntimeException
	 */
	protected function assertNotEmpty($value, $message)
	{
		$this->assert(!empty($value), $message);
	}

	/**
	 * Assert that $value is set to one of $validValues or throw a RuntimeException with the $message language string
	 *
	 * @param   mixed   $value        The value to check
	 * @param   array   $validValues  An array of valid values for $value
	 * @param   string  $message      The language key for the message to throw
	 *
	 * @throws  RuntimeException
	 */
	protected function assertInArray($value, array $validValues, $message)
	{
		$this->assert(in_array($value, $validValues), $message);
	}

	/**
	 * Assert that $value is set to none of $validValues. Otherwise throw a RuntimeException with the $message language
	 * string.
	 *
	 * @param   mixed   $value        The value to check
	 * @param   array   $validValues  An array of invalid values for $value
	 * @param   string  $message      The language key for the message to throw
	 *
	 * @throws  \RuntimeException
	 */
	protected function assertNotInArray($value, array $validValues, $message)
	{
		$this->assert(!in_array($value, $validValues, true), $message);
	}
}
