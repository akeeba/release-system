<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Autoloader for the end-to-end suite's own classes.
 *
 * Shared by bootstrap-e2e.php and provision.php. Note that no Joomla is loaded here, and none is
 * needed: the suite talks to the site over HTTP and to its database over PDO, exactly as an outside
 * observer would. That is the whole point — anything it can only see by loading the site's own code
 * in-process is not something a real request could see either.
 */

defined('_JEXEC') or define('_JEXEC', 1);

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'Akeeba\\ARS\\IntegrationTest\\';

		if (strncmp($class, $prefix, strlen($prefix)) !== 0)
		{
			return;
		}

		$relative = substr($class, strlen($prefix));
		$file     = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

		if (is_file($file))
		{
			require_once $file;
		}
	}
);
