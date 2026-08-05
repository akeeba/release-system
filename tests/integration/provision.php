<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Provision the ARS fixtures against an already-running stack.
 *
 * docker/run.sh calls this after installing ARS. You can also run it by hand against a stack left
 * up with --keep-containers, to put the fixtures back the way they started:
 *
 *     php tests/integration/provision.php
 */

require_once __DIR__ . '/autoload.php';

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\Engine\Configuration;
use Akeeba\ARS\IntegrationTest\SiteProvisioner;

$config = Configuration::getInstance();

fwrite(STDOUT, sprintf("Provisioning ARS fixtures (config: %s)\n", $config->getSourceFile()));

try
{
	$manifest = (new SiteProvisioner($config))->provision();
}
catch (Throwable $e)
{
	fwrite(STDERR, $e->getMessage() . "\n");

	exit(1);
}

fwrite(
	STDOUT,
	sprintf(
		"  %d user groups, %d users, %d categories, %d releases, %d items, %d update streams, %d Download IDs\n",
		count($manifest['groups'] ?? []),
		count($manifest['users'] ?? []),
		count($manifest['categories'] ?? []),
		count($manifest['releases'] ?? []),
		count($manifest['items'] ?? []),
		count($manifest['updateStreams'] ?? []),
		count($manifest['dlids'] ?? [])
	)
);

exit(0);
