<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\Amazon\Aws;


// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Custom autoloader for AWS PHP SDK, written for ARS
 */
class Autoloader
{
	/**
	 * An instance of this autoloader
	 *
	 * @var   Autoloader
	 */
	public static $autoloader = null;

	/**
	 * The path to the AWS root directory
	 *
	 * @var   string
	 */
	public static $awsPath = null;

	/**
	 * Initialise this autoloader
	 *
	 * @return  Autoloader
	 */
	public static function init()
	{
		if (self::$autoloader == null)
		{
			self::$autoloader = new self;
		}

		return self::$autoloader;
	}

	/**
	 * Public constructor. Registers the autoloader with PHP.
	 */
	public function __construct()
	{
		self::$awsPath = __DIR__;

		spl_autoload_register(array($this, 'autoload_aws'));
	}

	/**
	 * The actual autoloader
	 *
	 * @param   string  $className  The name of the class to load
	 *
	 * @return  void
	 */
	public function autoload_aws($className)
	{
		// Trim the trailing backslash
		$className = ltrim($className, '\\');

		// Make sure the class has an Akeeba\Engine prefix
		if (substr($className, 0, 17) != 'Akeeba\\ARS\\Amazon')
		{
			return;
		}

		// Remove the prefix and explode on backslashes
		$className = substr($className, 18);
		$class = explode('\\', $className);

		$rootPath = self::$awsPath;

		// First try finding in structured directory format (preferred)
		$path = $rootPath . '/' . implode('/', $class) . '.php';

		if (@file_exists($path))
		{
			include_once $path;
		}

		// Then try the duplicate last name structured directory format (not recommended)
		if (!class_exists($className, false))
		{
			reset($class);
			$lastPart = end($class);
			$path = $rootPath . '/' . implode('/', $class) . '/' . $lastPart . '.php';

			if (@file_exists($path))
			{
				include_once $path;
			}
		}
	}
}

// Register the AWS autoloader
Autoloader::init();