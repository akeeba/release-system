<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Bootstrap for the end-to-end suite.
 *
 * Deliberately tiny. It loads no Joomla and opens no database connection of its own: the suite
 * observes the site from outside, over HTTP, exactly as a browser or an attacker would. Anything it
 * could only see by loading the site's code in-process is not something a real request could see,
 * and asserting on it would be the illusion of coverage that this harness replaced.
 *
 * All it does is register the suite's autoloader and check that the stack is actually up, so a
 * forgotten `run.sh` produces one clear message instead of a hundred connection errors.
 */

require_once __DIR__ . '/autoload.php';

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\Engine\Configuration;

$config = Configuration::getInstance();

$probe = curl_init($config->getSiteUrl() . '/index.php');
curl_setopt_array(
	$probe,
	[
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_NOBODY         => true,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT        => 10,
	]
);
curl_exec($probe);
$siteReachable = curl_errno($probe) === 0;
$siteError     = curl_error($probe);

if (version_compare(PHP_VERSION, '8.5.0', 'lt')) {
	@curl_close($probe);
}

if (!$siteReachable) {
	fwrite(
		STDERR,
		sprintf(
			"The site under test is not reachable at %s (%s).\n\n"
			. "Configuration was read from: %s\n\n"
			. "Provision the stack first:\n"
			. "    tests/integration/docker/run.sh --keep-containers\n",
			$config->getSiteUrl(),
			$siteError,
			$config->getSourceFile()
		)
	);

	exit(1);
}
