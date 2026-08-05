<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Bootstrap for the unit test suite.
 *
 * The suite boots no Joomla and opens no database connection. It has no Composer dependencies
 * either: ARS ships none at runtime, so requiring `composer install` before you could run a unit
 * test would be a prerequisite that buys nothing. This file registers its own PSR-4 autoloader for
 * the ARS namespaces and loads a small set of Joomla symbol stubs.
 *
 * If a Composer autoloader happens to be present it is loaded first, so a real class always wins
 * over a stub.
 *
 * @see UnitTest/Stubs/joomla-stubs.php for what is stubbed, and why the list is deliberately short.
 */

// Required for ARS classes, every one of which guards against direct web access.
define('_JEXEC', 1);

$repositoryRoot = \dirname(__DIR__);

// Joomla path constants. Several helpers read these; a few write below JPATH_CACHE, so that one
// points at a per-run temporary directory rather than anywhere in the checkout.
define('JPATH_ROOT', $repositoryRoot);
define('JPATH_BASE', $repositoryRoot);
define('JPATH_SITE', $repositoryRoot);
define('JPATH_ADMINISTRATOR', $repositoryRoot . '/administrator');
define('JPATH_LIBRARIES', $repositoryRoot . '/libraries');
define('JPATH_PLUGINS', $repositoryRoot . '/plugins');
define('JPATH_CACHE', sys_get_temp_dir() . '/ars-unittest-cache');

if (!is_dir(JPATH_CACHE))
{
	@mkdir(JPATH_CACHE, 0777, true);
}

// Composer's autoloader, when the optional `composer install` has been run. Nothing here needs it.
if (is_file($repositoryRoot . '/vendor/autoload.php'))
{
	require_once $repositoryRoot . '/vendor/autoload.php';
}

/**
 * PSR-4 autoloader for the ARS namespaces.
 *
 * Note that the namespaces deliberately do not mirror the directory names — `backend/src` maps to
 * `…\Administrator\` and `frontend/src` to `…\Site\`. See the repository CLAUDE.md.
 */
spl_autoload_register(
	static function (string $class): void {
		static $prefixes = [
			'Akeeba\\Component\\ARS\\Administrator\\'  => '/component/backend/src',
			'Akeeba\\Component\\ARS\\Site\\'           => '/component/frontend/src',
			'Akeeba\\Component\\ARS\\Api\\'            => '/component/api/src',
			'Akeeba\\Plugin\\Content\\ARSDownloadID\\' => '/plugins/content/arsdlid/src',
			'Akeeba\\Plugin\\Content\\ARSLatest\\'     => '/plugins/content/arslatest/src',
			'Akeeba\\Plugin\\EditorsExtended\\ARSLink\\' => '/plugins/editors-xtd/arslink/src',
			'Akeeba\\Plugin\\WebServices\\ARS\\'       => '/plugins/webservices/ars/src',
			// Joomla's module namespaces carry a client segment (Site / Administrator) that is
			// part of the prefix, not of the path on disk.
			'Joomla\\Module\\Arsdownload\\Site\\'      => '/modules/site/arsdownloads/src',
			'Joomla\\Module\\Arsgraph\\Administrator\\' => '/modules/admin/arsgraph/src',
			'Akeeba\\ARS\\UnitTest\\'                  => '/UnitTest',
		];

		foreach ($prefixes as $prefix => $directory)
		{
			if (strncmp($class, $prefix, \strlen($prefix)) !== 0)
			{
				continue;
			}

			$relative = substr($class, \strlen($prefix));
			$file     = JPATH_ROOT . $directory . '/' . str_replace('\\', '/', $relative) . '.php';

			if (is_file($file))
			{
				require_once $file;
			}

			return;
		}
	}
);

// Minimal Joomla symbol stubs, so ARS classes that extend or reference the CMS can be loaded.
require_once __DIR__ . '/Stubs/joomla-stubs.php';

// Enable verbose errors and notices.
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Set the timezone to UTC to avoid surprises.
@date_default_timezone_set('UTC');

// Set the default locale, if the `intl` extension is present.
if (function_exists('locale_set_default'))
{
	locale_set_default('en_US.UTF-8');
}
