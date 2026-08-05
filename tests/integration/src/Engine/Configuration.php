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
 * The end-to-end suite's view of the provisioned site.
 *
 * Reads tests/integration/config.php, which docker/run.sh regenerates on every run. When that file
 * is absent — a fresh checkout, or someone running PHPUnit before ever running run.sh — we fall back
 * to config.dist.php, which carries the same defaults as docker/env.dist.
 *
 * That fallback is deliberate. The previous harness required a git-ignored .env with no committed
 * template, so a fresh clone could not run the suite at all and there was nothing to read to find
 * out why. Every setting here has a committed default.
 *
 * @since 7.5.0
 */
final class Configuration
{
	/**
	 * The singleton instance.
	 *
	 * @var   self|null
	 * @since 7.5.0
	 */
	private static ?self $instance = null;

	/**
	 * The loaded configuration.
	 *
	 * @var   array
	 * @since 7.5.0
	 */
	private array $data;

	/**
	 * Path to the file the configuration was loaded from.
	 *
	 * @var   string
	 * @since 7.5.0
	 */
	private string $sourceFile;

	/**
	 * Constructor.
	 *
	 * @param   array   $data        The configuration data.
	 * @param   string  $sourceFile  Where it came from.
	 *
	 * @since   7.5.0
	 */
	private function __construct(array $data, string $sourceFile)
	{
		$this->data       = $data;
		$this->sourceFile = $sourceFile;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return  self
	 * @since   7.5.0
	 */
	public static function getInstance(): self
	{
		if (self::$instance !== null)
		{
			return self::$instance;
		}

		$root      = \dirname(__DIR__, 2);
		$generated = $root . '/config.php';
		$template  = $root . '/config.dist.php';
		$file      = is_file($generated) ? $generated : $template;

		if (!is_file($file))
		{
			throw new RuntimeException(
				sprintf('Neither %s nor %s exists. Run tests/integration/docker/run.sh.', $generated, $template)
			);
		}

		$data = require $file;

		if (!is_array($data))
		{
			throw new RuntimeException(sprintf('%s did not return a configuration array.', $file));
		}

		return self::$instance = new self($data, $file);
	}

	/**
	 * Reset the singleton. For the harness's own tests only.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public static function reset(): void
	{
		self::$instance = null;
	}

	/**
	 * Read a configuration value by dotted path, e.g. 'db.host'.
	 *
	 * @param   string  $path     The dotted path.
	 * @param   mixed   $default  Returned when the path is absent.
	 *
	 * @return  mixed
	 * @since   7.5.0
	 */
	public function get(string $path, $default = null)
	{
		$value = $this->data;

		foreach (explode('.', $path) as $segment)
		{
			if (!is_array($value) || !array_key_exists($segment, $value))
			{
				return $default;
			}

			$value = $value[$segment];
		}

		return $value;
	}

	/**
	 * The base URL of the Apache-served site.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getSiteUrl(): string
	{
		return rtrim((string) $this->get('site.url', 'http://localhost:8100'), '/');
	}

	/**
	 * The base URL of the site, as reachable from inside the compose network.
	 *
	 * Used for server-side fetches — anything the site itself needs to reach itself over HTTP —
	 * where `localhost:8100` (the host-side mapping) would not resolve from inside the container.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getInternalUrl(): string
	{
		return rtrim((string) $this->get('site.internalUrl', 'http://web'), '/');
	}

	/**
	 * Absolute path to the provisioned site's document root on the host.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getSiteRoot(): string
	{
		return (string) $this->get('site.root', \dirname(__DIR__, 2) . '/docker/www');
	}

	/**
	 * The Joomla version that was provisioned.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getJoomlaVersion(): string
	{
		return (string) $this->get('joomlaVersion', '0.0.0');
	}

	/**
	 * The database table prefix of the provisioned site.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getDbPrefix(): string
	{
		return (string) $this->get('db.prefix', 'e2e_');
	}

	/**
	 * The password shared by every provisioned non-admin account.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getUserPassword(): string
	{
		return (string) $this->get('users.password', 'test');
	}

	/**
	 * The Super User's credentials, as [username, password].
	 *
	 * @return  array{0: string, 1: string}
	 * @since   7.5.0
	 */
	public function getAdminCredentials(): array
	{
		return [
			(string) $this->get('users.adminUsername', 'admin'),
			(string) $this->get('users.adminPassword', 'test'),
		];
	}

	/**
	 * The base URL of Mailpit's REST API.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getMailpitUrl(): string
	{
		return rtrim((string) $this->get('mailpit.url', 'http://localhost:8135'), '/');
	}

	/**
	 * The shared secret the identity probe requires.
	 *
	 * Derived from the site root path, so it differs per checkout and never needs to be stored. The
	 * probe is a test fixture on a throwaway container; the secret exists so that a stray request
	 * cannot reach it, not as a security boundary.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getProbeSecret(): string
	{
		return substr(hash('sha256', 'ars-e2e-probe|' . $this->getSiteRoot()), 0, 32);
	}

	/**
	 * The docker compose invocation, for the few operations that need one.
	 *
	 * @return  array{bin: string, file: string, php: string}
	 * @since   7.5.0
	 */
	public function getDocker(): array
	{
		return [
			'bin'  => (string) $this->get('docker.composeBin', 'docker compose'),
			'file' => (string) $this->get('docker.composeFile', \dirname(__DIR__, 2) . '/docker/docker-compose.yml'),
			'php'  => (string) $this->get('docker.phpService', 'php'),
		];
	}

	/**
	 * The ARS category `directory` value: where release files live, relative to the site root.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getRepositoryDirectory(): string
	{
		return (string) $this->get('repository', 'arsrepo');
	}

	/**
	 * Where the configuration was loaded from. Used in diagnostics.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function getSourceFile(): string
	{
		return $this->sourceFile;
	}
}
