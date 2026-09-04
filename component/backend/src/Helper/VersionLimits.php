<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Helper;

defined('_JEXEC') || die;

/**
 * Class for managing version limits and compatibility checks for a Joomla extension.
 *
 * This class provides functionality to define and enforce compatibility constraints for PHP and Joomla versions. It is
 * part of our compliance with the European Union Cyber Resiliency Act (EU CRA).
 *
 * Each version of our software is designed to support very specific PHP and Joomla versions. We can only support its
 * operation and ascertain the validity of our security risk analysis within the confines of these specific versions.
 * Therefore, we now have to enforce the compatibility checks and refuse operation in unsupported environments.
 *
 * Please note that modifying the software, including circumventing this enforcement, makes you the manufacturer of your
 * modified copy and relieves us from any obligation to provide support, bug fixes, and security updates to you.
 *
 * @since  7.5.1
 */
class VersionLimits
{
	/**
	 * The name of the software.
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $softwareName = 'Akeeba Release System';

	/**
	 * Does this software have distinct Core and Pro versions?
	 *
	 * @var   bool
	 * @since 7.5.1
	 */
	private static bool $supportsProCore = false;

	/**
	 * The name of the constant which indicates if this is a Core or Pro version. Use AK_NO_CONSTANT if none.
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $proCoreConstant = 'AK_NO_CONSTANT';

	/**
	 * Minimum supported PHP version
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $minPHPVersion = '8.1.0';

	/**
	 * Maximum supported PHP version
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $maxPHPVersion = '8.7';

	/**
	 * Minimum supported Joomla version
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $minJoomlaVersion = '5.4.0';

	/**
	 * Maximum supported Joomla version
	 *
	 * @var   string
	 * @since 7.5.1
	 */
	private static string $maxJoomlaVersion = '6.3';

	/**
	 * Cached incompatibility reasons.
	 *
	 * @var   array{php_min: bool, php_max: bool, joomla_min: bool, joomla_max: bool}
	 * @since 7.5.1
	 */
	private static array $incompatibleReason = [];

	/**
	 * Determines if the environment is compatible with the software by evaluating all version compatibility checks.
	 *
	 * @return  bool  Returns true if the system is compatible.
	 * @since   7.5.1
	 */
	public static function isCompatible(): bool
	{
		self::checkCompatibility();

		return array_reduce(self::$incompatibleReason, fn(bool $carry, bool $item) => $carry && !$item, true);
	}

	/**
	 * Throws a RuntimeException if the PHP and Joomla version compatibliity checks are not met.
	 *
	 * This method checks the PHP and Joomla versions against the required minimum and maximum versions.
	 * If the environment does not meet the compatibility requirements, a RuntimeException is thrown
	 * with an appropriate message indicating the version issue.
	 *
	 * @return  void
	 * @since   7.5.1
	 *
	 * @throws  \RuntimeException If the environment is incompatible with the required versions of PHP or Joomla.
	 */
	public static function throwIfVersionsIncompatible(): void
	{
		if (self::isCompatible())
		{
			return;
		}

		$softwareName = self::getSoftwareName();

		if (self::$incompatibleReason['php_min'])
		{
			throw new \RuntimeException(
				sprintf(
					'This version of %s requires PHP %s or later. Your server currently uses PHP version %s. Please upgrade your PHP version.',
					$softwareName, self::$minPHPVersion, PHP_VERSION
				)
			);
		}

		if (self::$incompatibleReason['php_max'])
		{
			throw new \RuntimeException(
				sprintf(
					'This version of %s is only compatible with PHP versions lower than %s. Your server currently uses PHP version %s. Please upgrade %1$s.',
					$softwareName, self::$maxPHPVersion, PHP_VERSION
				)
			);

		}

		if (self::$incompatibleReason['joomla_min'])
		{
			throw new \RuntimeException(
				sprintf(
					'This version of %s requires Joomla %s or later. You are currently using Joomla %s. Please upgrade Joomla.',
					$softwareName, self::$minJoomlaVersion, JVERSION
				)
			);
		}

		if (self::$incompatibleReason['joomla_max'])
		{
			throw new \RuntimeException(
				sprintf(
					'This version of %s is only compatible with Joomla versions lower than %s. You are currently using Joomla %s. Please upgrade %1$s.',
					$softwareName, self::$maxJoomlaVersion, JVERSION
				)
			);
		}
	}

	/**
	 * Determines if the current PHP version is below the required minimum version.
	 *
	 * @return  bool
	 * @since   7.5.1
	 */
	public static function isPHPTooLow(): bool
	{
		self::checkCompatibility();

		return self::$incompatibleReason['php_min'];
	}

	/**
	 * Determines if the current PHP version is above the supported maximum version.
	 *
	 * @return  bool
	 * @since   7.5.1
	 */
	public static function isPHPTooHigh(): bool
	{
		self::checkCompatibility();

		return self::$incompatibleReason['php_max'];
	}

	/**
	 * Determines if the current Joomla version is below the required minimum version.
	 *
	 * @return  bool
	 * @since   7.5.1
	 */
	public static function isJoomlaTooLow(): bool
	{
		self::checkCompatibility();

		return self::$incompatibleReason['joomla_min'];
	}

	/**
	 * Determines if the current Joomla version is above the supported maximum version.
	 *
	 * @return  bool
	 * @since   7.5.1
	 */
	public static function isJoomlaTooHigh(): bool
	{
		self::checkCompatibility();

		return self::$incompatibleReason['joomla_max'];
	}

	/**
	 * Checks the compatibility of the environment with the required PHP and Joomla versions.
	 *
	 * Updates the internal state with incompatibility reasons if the current versions do not meet the defined
	 * requirements.
	 *
	 * @return  void
	 * @since   7.5.1
	 */
	private static function checkCompatibility(): void
	{
		self::$incompatibleReason['php_min']    ??= version_compare(PHP_VERSION, self::$minPHPVersion, 'lt');
		self::$incompatibleReason['php_max']    ??= version_compare(PHP_VERSION, self::$maxPHPVersion, 'ge');
		self::$incompatibleReason['joomla_min'] ??= version_compare(JVERSION, self::$minJoomlaVersion, 'lt');
		self::$incompatibleReason['joomla_max'] ??= version_compare(JVERSION, self::$maxJoomlaVersion, 'ge');
	}

	/**
	 * Retrieves the software name, including either "Professional" or "Core" based on the current configuration.
	 *
	 * If the software is not designed to have distinct Core / Pro versions, or the constant which determines that is
	 * not defined, it will return the software name without any additional suffix.
	 *
	 * @return  string  The formatted software name.
	 * @since   7.5.1
	 */
	private static function getSoftwareName(): string
	{
		$ret = self::$softwareName;

		if (!self::$supportsProCore || defined(self::$proCoreConstant))
		{
			return $ret;
		}

		return rtrim($ret) . ' ' . (constant(self::$proCoreConstant) ? 'Professional' : 'Core');
	}
}
