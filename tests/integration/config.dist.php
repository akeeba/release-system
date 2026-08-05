<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * Defaults for the end-to-end suite.
 *
 * docker/run.sh writes a config.php next to this file with the values it actually provisioned; this
 * template is what the suite falls back to when that file is absent. Keep it in step with
 * docker/env.dist — between the two, a fresh clone can run the suite without configuring anything.
 *
 * To point the suite at a site you provisioned some other way, copy this file to config.php (which
 * is git-ignored) and edit it.
 */
return [
	'site'    => [
		// The Apache front-end, as seen from the host running PHPUnit.
		'url'         => 'http://localhost:8100',
		// Reachable from inside the compose network (server-side fetches).
		'internalUrl' => 'http://web',
		'root'        => __DIR__ . '/docker/www',
	],
	'db'      => [
		'host'   => '127.0.0.1',
		'port'   => 33308,
		'name'   => 'arse2e',
		'user'   => 'arse2e',
		'pass'   => 'arse2e',
		'prefix' => 'e2e_',
	],
	'mailpit' => [
		'url' => 'http://localhost:8135',
	],
	'docker'  => [
		'composeBin'  => 'docker compose',
		'composeFile' => __DIR__ . '/docker/docker-compose.yml',
		'phpService'  => 'php',
	],
	'users'   => [
		'adminUsername' => 'admin',
		'adminPassword' => 'test',
		'password'      => 'test',
	],
	'mail'    => [
		'from'     => 'releases@example.test',
		'fromName' => 'ARS Releases',
	],
	// Overwritten by run.sh with the version it actually resolved and installed.
	'joomlaVersion' => '0.0.0',
	// The ARS category `directory` value: where release files live, relative to the site root.
	'repository'    => 'arsrepo',
];
