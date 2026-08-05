<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Engine;

defined('_JEXEC') or die;

use RuntimeException;

/**
 * Runs a command inside the site's php container.
 *
 * Needed for the parts of ARS that are not reachable over HTTP at all — the scheduled tasks. Those
 * run through Joomla's console application, and driving them any other way (calling the helper
 * in-process, POSTing to the front-end CRON endpoint) would exercise something other than what a
 * real cron entry does.
 *
 * @since 7.5.0
 */
class ContainerCli
{
	/**
	 * The suite configuration.
	 *
	 * @var   Configuration
	 * @since 7.5.0
	 */
	private Configuration $config;

	/**
	 * Constructor.
	 *
	 * @param   Configuration|null  $config  The suite configuration.
	 *
	 * @since   7.5.0
	 */
	public function __construct(?Configuration $config = null)
	{
		$this->config = $config ?? Configuration::getInstance();
	}

	/**
	 * Run Joomla's console application inside the container.
	 *
	 * @param   string[]  $arguments  Arguments after `cli/joomla.php`, e.g. ['ars:cron', 'something'].
	 *
	 * @return  array{0: int, 1: string}  Exit code and combined output.
	 * @since   7.5.0
	 */
	public function joomla(array $arguments): array
	{
		return $this->run(array_merge(['php', 'cli/joomla.php'], $arguments));
	}

	/**
	 * Run Joomla's console application, failing loudly on a non-zero exit.
	 *
	 * @param   string[]  $arguments  Arguments after `cli/joomla.php`.
	 *
	 * @return  string  The combined output.
	 * @throws  RuntimeException  When the command fails.
	 * @since   7.5.0
	 */
	public function joomlaOrFail(array $arguments): string
	{
		[$exitCode, $output] = $this->joomla($arguments);

		if ($exitCode !== 0)
		{
			throw new RuntimeException(
				sprintf("`cli/joomla.php %s` failed (exit %d):\n%s", implode(' ', $arguments), $exitCode, $output)
			);
		}

		return $output;
	}

	/**
	 * Run an arbitrary command inside the php container.
	 *
	 * @param   string[]  $command  The command and its arguments.
	 *
	 * @return  array{0: int, 1: string}  Exit code and combined output.
	 * @since   7.5.0
	 */
	public function run(array $command): array
	{
		$docker = $this->config->getDocker();

		$parts = array_merge(
			// composeBin may be "docker compose" (two words) or "docker-compose".
			explode(' ', $docker['bin']),
			['-f', $docker['file'], 'exec', '-T', '-w', '/var/www/html', $docker['php']],
			$command
		);

		$escaped = implode(' ', array_map('escapeshellarg', $parts)) . ' 2>&1';

		exec($escaped, $output, $exitCode);

		return [$exitCode, implode("\n", $output)];
	}
}
