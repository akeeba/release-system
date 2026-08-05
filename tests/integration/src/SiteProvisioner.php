<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\Engine\Configuration;
use Akeeba\ARS\IntegrationTest\Engine\ContainerCli;
use RuntimeException;

/**
 * Creates and reads back the ARS fixtures on the provisioned site.
 *
 * Host-side façade. The work itself happens inside the php container, in assets/e2e-provision.php,
 * because Joomla's nested sets and asset tree are not something to reimplement in SQL — see that
 * file's header for the full reasoning and for the ACL user matrix it builds.
 *
 * What this class adds is the part the tests actually touch: run or re-run the provisioner, then
 * hand out the ids and usernames it created, by name rather than by magic number.
 *
 * @since 7.5.0
 */
class SiteProvisioner
{
	/**
	 * Filename of the provisioning script inside the site root.
	 *
	 * @since 7.5.0
	 */
	private const SCRIPT = 'e2e-provision.php';

	/**
	 * Filename of the manifest the provisioning script writes.
	 *
	 * @since 7.5.0
	 */
	private const MANIFEST = 'e2e-manifest.json';

	/**
	 * The suite configuration.
	 *
	 * @var   Configuration
	 * @since 7.5.0
	 */
	private Configuration $config;

	/**
	 * Runs commands inside the site's php container.
	 *
	 * @var   ContainerCli
	 * @since 7.5.0
	 */
	private ContainerCli $cli;

	/**
	 * The manifest of everything the provisioner created.
	 *
	 * @var   array|null
	 * @since 7.5.0
	 */
	private ?array $manifest = null;

	/**
	 * Shared instance.
	 *
	 * @var   self|null
	 * @since 7.5.0
	 */
	private static ?self $instance = null;

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
		$this->cli    = new ContainerCli($this->config);
	}

	/**
	 * The shared instance, so a whole PHPUnit run provisions once by default.
	 *
	 * @return  self
	 * @since   7.5.0
	 */
	public static function getInstance(): self
	{
		return self::$instance ??= new self();
	}

	/**
	 * Run the provisioner, creating the fixtures from scratch.
	 *
	 * Re-running is a reset, not a duplication: the provisioning script truncates the ARS content
	 * tables before it inserts.
	 *
	 * @return  array  The manifest.
	 * @since   7.5.0
	 */
	public function provision(): array
	{
		$this->deployScript();

		[$exitCode, $output] = $this->cli->run(
			['php', self::SCRIPT, $this->config->getUserPassword()]
		);

		if ($exitCode !== 0)
		{
			throw new RuntimeException(
				sprintf("Fixture provisioning failed (exit %d):\n%s", $exitCode, $output)
			);
		}

		SiteProbe::deploy($this->config);

		return $this->manifest = $this->readManifest();
	}

	/**
	 * Re-run the provisioner, discarding whatever the tests have done to the fixtures.
	 *
	 * @return  array  The manifest.
	 * @since   7.5.0
	 */
	public function reset(): array
	{
		return $this->provision();
	}

	/**
	 * The manifest, provisioning first if it has not been done yet in this process.
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	public function getManifest(): array
	{
		if ($this->manifest !== null)
		{
			return $this->manifest;
		}

		$file = $this->siteRoot() . '/' . self::MANIFEST;

		if (is_file($file))
		{
			return $this->manifest = $this->readManifest();
		}

		return $this->provision();
	}

	/**
	 * The numeric id of a provisioned user, by role.
	 *
	 * @param   string  $role  One of the keys in the manifest's `users` map, e.g. 'manager'.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function userId(string $role): int
	{
		return (int) $this->lookup('users', $role);
	}

	/**
	 * The username of a provisioned user, by role.
	 *
	 * @param   string  $role  One of the keys in the manifest's `usernames` map.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function username(string $role): string
	{
		return (string) $this->lookup('usernames', $role);
	}

	/**
	 * The id of a provisioned user group.
	 *
	 * @param   string  $name  e.g. 'managers', 'downloaders'.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function groupId(string $name): int
	{
		return (int) $this->lookup('groups', $name);
	}

	/**
	 * The id of a provisioned ARS category.
	 *
	 * @param   string  $name  A category name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function categoryId(string $name): int
	{
		return (int) $this->lookup('categories', $name);
	}

	/**
	 * The id of a provisioned release.
	 *
	 * @param   string  $name  A release name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function releaseId(string $name): int
	{
		return (int) $this->lookup('releases', $name);
	}

	/**
	 * The id of a provisioned item (a downloadable file, link or documentation entry).
	 *
	 * @param   string  $name  An item name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function itemId(string $name): int
	{
		return (int) $this->lookup('items', $name);
	}

	/**
	 * The id of a provisioned update stream.
	 *
	 * @param   string  $name  An update stream name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function updateStreamId(string $name): int
	{
		return (int) $this->lookup('updateStreams', $name);
	}

	/**
	 * The id of a provisioned auto-description.
	 *
	 * @param   string  $name  An auto-description name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function autoDescriptionId(string $name): int
	{
		return (int) $this->lookup('autoDescriptions', $name);
	}

	/**
	 * The id of a provisioned update stream environment.
	 *
	 * @param   string  $name  An environment name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function environmentId(string $name): int
	{
		return (int) $this->lookup('environments', $name);
	}

	/**
	 * The id of a provisioned Download ID label.
	 *
	 * @param   string  $name  A Download ID label name from the fixture manifest.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function dlidLabelId(string $name): int
	{
		return (int) $this->lookup('dlidLabels', $name);
	}

	/**
	 * A provisioned Download ID, by role.
	 *
	 * @param   string  $role  One of the keys in the manifest's `dlids` map.
	 *
	 * @return  string  The 32-character Download ID.
	 * @since   7.5.0
	 */
	public function dlid(string $role): string
	{
		return (string) $this->lookup('dlids', $role);
	}

	/**
	 * A provisioned API token, by role.
	 *
	 * @param   string  $role  One of the keys in the manifest's `apiTokens` map.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function apiToken(string $role): string
	{
		return (string) $this->lookup('apiTokens', $role);
	}

	/**
	 * The id of a provisioned view access level.
	 *
	 * @param   string  $name  A view level name from the fixture manifest, e.g. 'restricted'.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	public function viewLevelId(string $name): int
	{
		return (int) $this->lookup('viewLevels', $name);
	}

	/**
	 * The provisioned fixture file for an item.
	 *
	 * @param   string  $itemName  An item name from the fixture manifest.
	 *
	 * @return  array{relative: string, absolute: string, size: int, sha256: string, sentinel: string}
	 * @since   7.5.0
	 */
	public function file(string $itemName): array
	{
		return (array) $this->lookup('files', $itemName);
	}

	/**
	 * Where the fixture repository lives, relative to the site root.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	public function repositoryPath(): string
	{
		$manifest = $this->getManifest();

		return (string) ($manifest['repository']['relative'] ?? '');
	}

	/**
	 * Read a value out of a manifest section, failing loudly when it is absent.
	 *
	 * A missing key here means the fixture the test needs was never created. Returning null and
	 * letting the test carry on would produce a request for item id 0, which the site quite
	 * correctly refuses — and the test would pass while proving nothing.
	 *
	 * @param   string  $section  The manifest section.
	 * @param   string  $key      The key within it.
	 *
	 * @return  mixed
	 * @since   7.5.0
	 */
	private function lookup(string $section, string $key)
	{
		$manifest = $this->getManifest();

		if (!isset($manifest[$section]) || !array_key_exists($key, $manifest[$section]))
		{
			throw new RuntimeException(
				sprintf(
					'The fixture manifest has no %s named "%s". Known: %s',
					rtrim($section, 's'),
					$key,
					implode(', ', array_keys($manifest[$section] ?? [])) ?: '(none)'
				)
			);
		}

		return $manifest[$section][$key];
	}

	/**
	 * Copy the provisioning script into the site root.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function deployScript(): void
	{
		$source = \dirname(__DIR__) . '/assets/' . self::SCRIPT;
		$target = $this->siteRoot() . '/' . self::SCRIPT;

		if (!is_file($source))
		{
			throw new RuntimeException(sprintf('The provisioning script %s is missing.', $source));
		}

		if (!copy($source, $target))
		{
			throw new RuntimeException(sprintf('Could not copy the provisioning script to %s.', $target));
		}
	}

	/**
	 * Read and decode the manifest the provisioning script wrote.
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	private function readManifest(): array
	{
		$file = $this->siteRoot() . '/' . self::MANIFEST;

		if (!is_file($file))
		{
			throw new RuntimeException(
				sprintf('No fixture manifest at %s. Run tests/integration/docker/run.sh.', $file)
			);
		}

		$data = json_decode((string) file_get_contents($file), true);

		if (!is_array($data))
		{
			throw new RuntimeException(sprintf('The fixture manifest at %s is not valid JSON.', $file));
		}

		return $data;
	}

	/**
	 * Absolute path to the provisioned site's document root on the host.
	 *
	 * @return  string
	 * @since   7.5.0
	 */
	private function siteRoot(): string
	{
		$root = rtrim($this->config->getSiteRoot(), '/');

		if (!is_dir($root))
		{
			throw new RuntimeException(
				sprintf('The site root %s does not exist. Run tests/integration/docker/run.sh first.', $root)
			);
		}

		return $root;
	}
}
