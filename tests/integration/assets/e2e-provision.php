<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Fixture provisioner for the ARS end-to-end test suite.
 *
 * THIS IS A TEST FIXTURE, not production code. SiteProvisioner copies this file into the throwaway
 * site's document root and runs it inside the php container. It creates the ACL user matrix, the
 * categories, and the release/item data the suite asserts against, then writes a JSON manifest of
 * every id it created.
 *
 * WHY THIS RUNS INSIDE THE CONTAINER, AND NOT AS HOST-SIDE SQL
 *
 * Joomla's user groups, view access levels and the asset tree hanging off them are nested sets with
 * bookkeeping that is easy to get subtly wrong by hand — and debugging ACL results that are wrong for
 * reasons that have nothing to do with ARS is exactly the kind of thing this fixture exists to avoid.
 * Joomla's own Table classes already do it correctly, so we use them (Usergroup, ViewLevel, User) and
 * the fixtures end up shaped exactly like a real site's.
 *
 * ARS's OWN tables follow the same idea, but with a twist worth knowing before you read further:
 * `#__ars_categories` has an `asset_id` column, so Joomla's generic Table asset-tracking switches
 * itself on for it automatically and CategoryTable::store() creates the `#__assets` row that
 * `com_ars.category.<id>` rules hang off. `#__ars_releases` and `#__ars_items` have NO `asset_id`
 * column, so ReleaseTable and ItemTable are ordinary, non-asset-tracked tables — there is no
 * `com_ars.release.<id>` or `com_ars.item.<id>` asset, ever. Unlike com_content, ARS categories are
 * also NOT a Joomla nested set (no lft/rgt/level/path columns): `CategoryTable` extends the plain
 * `Joomla\CMS\Table\Table`, not `Joomla\CMS\Table\Nested`, so there is no setLocation() dance here.
 *
 * We use the ARS Table classes (CategoryTable, ReleaseTable, ItemTable, UpdatestreamTable,
 * AutodescriptionTable, EnvironmentTable, DlidlabelTable) rather than hand-written INSERTs for
 * everything ARS-specific, specifically so their check()/onBeforeCheck() logic runs: alias
 * generation, hash/filesize computation, auto-description application, update-stream matching and
 * Download ID generation all happen there. Letting them run is the point — the fixtures then reflect
 * what ARS actually stores, not what this script assumes it stores.
 *
 * THE USER MATRIX IS THE POINT
 *
 * ARS has no custom ACL actions (see access.xml — only core.* at component and category level), so
 * unlike sibling projects the interesting asymmetries here are in view access levels, per-category
 * asset rules, and Download IDs. Every group, view level and record below exists because a specific
 * assertion turns on it. They are not decoration: a "catManager" who holds core.edit on the restricted
 * category but was never granted core.create there is the only way to tell whether save2copy checks
 * the right action, and a plain "manager" account alone would never reveal it.
 */

use Akeeba\Component\ARS\Administrator\Table\AbstractTable;
use Akeeba\Component\ARS\Administrator\Table\AutodescriptionTable;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\DlidlabelTable;
use Akeeba\Component\ARS\Administrator\Table\EnvironmentTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Akeeba\Component\ARS\Administrator\Table\UpdatestreamTable;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Usergroup;
use Joomla\CMS\Table\ViewLevel;
use Joomla\CMS\User\User;
use Joomla\Console\Application;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;

const _JEXEC = 1;

// ---------------------------------------------------------------------------
// Bootstrap, mirroring cli/joomla.php.
// ---------------------------------------------------------------------------
if (file_exists(__DIR__ . '/defines.php'))
{
	require_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', __DIR__);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

$container = Factory::getContainer();

$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\SessionInterface::class, 'session.cli');

$app                  = $container->get(Application::class);
Factory::$application = $app;

/**
 * Load the extension PSR-4 map by hand.
 *
 * Verified in the Joomla source, not assumed: nothing in libraries/bootstrap.php or
 * includes/framework.php registers the extension namespaces. That is done by
 * ExtensionNamespaceMapper::createExtensionNamespaceMap(), which ConsoleApplication only calls from
 * doExecute(). This script boots the application but deliberately never executes it, so without this
 * line NO extension class is loadable — and the failure is silent and misleading, because every
 * extension file guards itself with `defined('_JEXEC') or die`. The file gets included, dies quietly,
 * and PHP reports the class as simply not found.
 */
$app->createExtensionNamespaceMap();

/** @var DatabaseDriver $db */
$db = $container->get(DatabaseInterface::class);

$password = $argv[1] ?? 'test';

// ---------------------------------------------------------------------------
// Small helpers, ported near-verbatim from the sibling projects' e2e harnesses.
// ---------------------------------------------------------------------------

/**
 * Find a user group by title, or create it under the given parent.
 */
function ensureGroup(string $title, int $parentId): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__usergroups'))
		->where($db->quoteName('title') . ' = :title')
		->bind(':title', $title);

	$existing = $db->setQuery($query)->loadResult();

	if ($existing)
	{
		return (int) $existing;
	}

	$table = new Usergroup($db);

	if (!$table->save(['title' => $title, 'parent_id' => $parentId]))
	{
		throw new RuntimeException(sprintf('Could not create user group "%s": %s', $title, $table->getError()));
	}

	return (int) $table->id;
}

/**
 * Look up a user group id by title. Throws if it is missing, because silently proceeding with the
 * wrong group would produce an ACL fixture that quietly grants the wrong thing.
 */
function groupId(string $title): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__usergroups'))
		->where($db->quoteName('title') . ' = :title')
		->bind(':title', $title);

	$id = $db->setQuery($query)->loadResult();

	if (!$id)
	{
		throw new RuntimeException(sprintf('No such user group: "%s".', $title));
	}

	return (int) $id;
}

/**
 * Find a view access level by title, or create it granting the given groups.
 */
function ensureViewLevel(string $title, array $groupIds): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__viewlevels'))
		->where($db->quoteName('title') . ' = :title')
		->bind(':title', $title);

	$existing = $db->setQuery($query)->loadResult();

	$table = new ViewLevel($db);

	if ($existing)
	{
		$table->load((int) $existing);
	}

	if (!$table->save([
		'id'       => $existing ? (int) $existing : 0,
		'title'    => $title,
		'ordering' => 0,
		'rules'    => json_encode(array_values(array_map('intval', $groupIds))),
	]))
	{
		throw new RuntimeException(sprintf('Could not create view level "%s": %s', $title, $table->getError()));
	}

	return (int) $table->id;
}

/**
 * Create a user, or update an existing one to match.
 */
function ensureUser(string $username, string $name, string $email, string $password, array $groupIds): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__users'))
		->where($db->quoteName('username') . ' = :username')
		->bind(':username', $username);

	$existing = (int) ($db->setQuery($query)->loadResult() ?: 0);

	$user = new User($existing ?: null);

	$data = [
		'id'        => $existing,
		'name'      => $name,
		'username'  => $username,
		'email'     => $email,
		'password'  => $password,
		'password2' => $password,
		'groups'    => array_values(array_map('intval', $groupIds)),
		'block'     => 0,
	];

	if (!$user->bind($data))
	{
		throw new RuntimeException(sprintf('Could not bind user "%s": %s', $username, $user->getError()));
	}

	if (!$user->save())
	{
		throw new RuntimeException(sprintf('Could not save user "%s": %s', $username, $user->getError()));
	}

	return (int) $user->id;
}

/**
 * Write an ACL rules set onto an asset, replacing whatever was there.
 *
 * $rules is [action => [groupId => 1|0]]. A 0 is a real Deny, not merely "not granted" — the
 * distinction matters wherever a fixture needs to prove that an explicit deny wins.
 */
function setAssetRules(string $assetName, array $rules): void
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	// Check that the asset exists with a SELECT, NOT by looking at the affected row count of the
	// UPDATE below. MySQL reports zero affected rows when the new value equals the old one, so an
	// affected-rows check answers "did this change anything?" rather than "does the asset exist?" —
	// and would therefore fail on every re-run, which is precisely when this script is being used to
	// reset the fixtures.
	$exists = $db->setQuery(
		$db->createQuery()
			->select('COUNT(*)')
			->from($db->quoteName('#__assets'))
			->where($db->quoteName('name') . ' = :name')
			->bind(':name', $assetName)
	)->loadResult();

	if (!$exists)
	{
		throw new RuntimeException(sprintf('No asset named "%s" to apply rules to.', $assetName));
	}

	$encoded = json_encode($rules);

	$query = $db->createQuery()
		->update($db->quoteName('#__assets'))
		->set($db->quoteName('rules') . ' = :rules')
		->where($db->quoteName('name') . ' = :name')
		->bind(':rules', $encoded)
		->bind(':name', $assetName);

	$db->setQuery($query)->execute();
}

/**
 * Set the com_ars component parameters, so the tests do not depend on shipped defaults drifting.
 */
function setComponentParams(array $params): void
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$encoded = json_encode($params);

	$query = $db->createQuery()
		->update($db->quoteName('#__extensions'))
		->set($db->quoteName('params') . ' = :params')
		->where($db->quoteName('element') . ' = ' . $db->quote('com_ars'))
		->where($db->quoteName('type') . ' = ' . $db->quote('component'))
		->bind(':params', $encoded);

	$db->setQuery($query)->execute();
}

/**
 * Enable a plugin and, optionally, set its parameters.
 */
function enablePlugin(string $folder, string $element, ?array $params = null): void
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->update($db->quoteName('#__extensions'))
		->set($db->quoteName('enabled') . ' = 1')
		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
		->where($db->quoteName('folder') . ' = :folder')
		->where($db->quoteName('element') . ' = :element')
		->bind(':folder', $folder)
		->bind(':element', $element);

	if ($params !== null)
	{
		$encoded = json_encode($params);
		$query->set($db->quoteName('params') . ' = :params')->bind(':params', $encoded);
	}

	$db->setQuery($query)->execute();
}

/**
 * Insert a row and return its id. insertObject() takes its object argument BY REFERENCE, so the
 * cast has to be assigned to a variable first.
 */
function insertRow(string $table, array $data): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$object = (object) $data;
	$db->insertObject($table, $object, 'id');

	return (int) $object->id;
}

/**
 * Insert a row into a table with no auto-increment primary key.
 */
function insertRowNoKey(string $table, array $data): void
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$object = (object) $data;
	$db->insertObject($table, $object);
}

/**
 * Grant a root-level ACL action to a group, MERGING into the existing rules.
 *
 * The root asset carries every global privilege on the site. Replacing its rules wholesale would
 * quietly revoke core.login.site, core.login.admin and core.admin from everyone, and the resulting
 * failures would look like ARS bugs.
 */
function grantRootAction(string $action, int $groupId): void
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$current = $db->setQuery(
		$db->createQuery()
			->select($db->quoteName('rules'))
			->from($db->quoteName('#__assets'))
			->where($db->quoteName('id') . ' = 1')
	)->loadResult();

	$rules = json_decode((string) $current, true);

	if (!is_array($rules))
	{
		throw new RuntimeException('The root asset has no readable rules.');
	}

	$rules[$action]           = $rules[$action] ?? [];
	$rules[$action][$groupId] = 1;

	$encoded = json_encode($rules);

	$db->setQuery(
		$db->createQuery()
			->update($db->quoteName('#__assets'))
			->set($db->quoteName('rules') . ' = :rules')
			->where($db->quoteName('id') . ' = 1')
			->bind(':rules', $encoded)
	)->execute();
}

/**
 * Issue a Joomla API token for a user, the same way plg_user_token does.
 *
 * The token is base64("sha256:<userId>:<hmac>") where the HMAC is over a random seed keyed with the
 * site secret; the seed itself is stored base64-encoded in #__user_profiles. Reproducing that here
 * rather than driving the back-end UI keeps provisioning to one step, and the token still has to
 * survive the real authentication plugin on every request.
 */
function issueApiToken(int $userId): string
{
	/** @var DatabaseDriver $db */
	$db     = Factory::getContainer()->get(DatabaseInterface::class);
	$secret = Factory::getApplication()->get('secret');
	$seed   = random_bytes(32);

	$db->setQuery(
		$db->createQuery()
			->delete($db->quoteName('#__user_profiles'))
			->where($db->quoteName('user_id') . ' = :userId')
			->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote('joomlatoken.%'))
			->bind(':userId', $userId, ParameterType::INTEGER)
	)->execute();

	foreach ([['joomlatoken.token', base64_encode($seed)], ['joomlatoken.enabled', '1']] as $ordering => [$key, $value])
	{
		insertRowNoKey(
			'#__user_profiles',
			[
				'user_id'       => $userId,
				'profile_key'   => $key,
				'profile_value' => $value,
				'ordering'      => $ordering,
			]
		);
	}

	return base64_encode('sha256:' . $userId . ':' . hash_hmac('sha256', $seed, $secret));
}

/**
 * An SQL datetime a given number of minutes in the past.
 */
function minutesAgo(int $minutes): string
{
	return (clone Factory::getDate())->sub(new DateInterval('PT' . $minutes . 'M'))->toSql();
}

// ---------------------------------------------------------------------------
// ARS-specific helpers.
// ---------------------------------------------------------------------------

/**
 * bind() + check() + store() an ARS table object, failing loudly and with context on any step.
 *
 * check() is TableAssertionTrait-backed for every ARS table: onBeforeCheck() throws a
 * RuntimeException directly on the first failed assertion, rather than returning false and setting
 * getError() the way plain Joomla tables do. store() still uses the getError()-on-false convention.
 * This helper copes with both so every ARS-specific INSERT in this script goes through one place.
 */
function storeTable(AbstractTable $table, array $data, string $label): AbstractTable
{
	if (!$table->bind($data))
	{
		throw new RuntimeException(sprintf('Could not bind %s: %s', $label, $table->getError()));
	}

	try
	{
		$table->check();
	}
	catch (\Throwable $e)
	{
		throw new RuntimeException(sprintf('Could not validate %s: %s', $label, $e->getMessage()), 0, $e);
	}

	if (!$table->store())
	{
		throw new RuntimeException(sprintf('Could not store %s: %s', $label, $table->getError()));
	}

	return $table;
}

/**
 * Find an ARS category by alias, or create/update it through CategoryTable.
 *
 * CategoryTable is asset-aware (_getAssetName() returns 'com_ars.category.<id>'), so store() creates
 * and wires the #__assets row that the per-category core.create/core.edit rules hang off. Hand-written
 * INSERTs would leave asset_id = 0 and every per-category authorisation assertion would pass or fail
 * for reasons unrelated to ARS.
 *
 * The category's `directory` must already exist on disk — CategoryTable::onBeforeCheck() asserts
 * is_dir(JPATH_SITE . '/' . $directory) — so the repository tree has to be built before this is called.
 */
function ensureArsCategory(array $data): int
{
	/** @var DatabaseDriver $db */
	$db    = Factory::getContainer()->get(DatabaseInterface::class);
	$alias = $data['alias'];

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__ars_categories'))
		->where($db->quoteName('alias') . ' = :alias')
		->bind(':alias', $alias);

	$existing = (int) ($db->setQuery($query)->loadResult() ?: 0);

	$table = new CategoryTable($db);

	if ($existing)
	{
		$table->load($existing);
	}

	$data['id'] = $existing ?: 0;

	storeTable($table, $data, sprintf('category "%s"', $alias));

	return (int) $table->id;
}

/**
 * Find an ARS environment by its xmltitle (e.g. 'joomla/6.1'), or create it through EnvironmentTable.
 *
 * Environments are ensure-style, like categories: the install seeds #__ars_environments and this
 * script only ever adds to it, so ids stay stable across resets.
 */
function ensureEnvironment(string $title, string $xmltitle): int
{
	/** @var DatabaseDriver $db */
	$db = Factory::getContainer()->get(DatabaseInterface::class);

	$query = $db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__ars_environments'))
		->where($db->quoteName('xmltitle') . ' = :xmltitle')
		->bind(':xmltitle', $xmltitle);

	$existing = (int) ($db->setQuery($query)->loadResult() ?: 0);

	$table = new EnvironmentTable($db);

	if ($existing)
	{
		$table->load($existing);
	}

	storeTable(
		$table,
		['id' => $existing ?: 0, 'title' => $title, 'xmltitle' => $xmltitle],
		sprintf('environment "%s"', $xmltitle)
	);

	return (int) $table->id;
}

/**
 * Ensure a single directory carries Joomla's standard folder-protection placeholder.
 */
function ensureIndexHtml(string $dir): void
{
	$marker = $dir . '/index.html';

	if (!is_file($marker))
	{
		file_put_contents($marker, '<!DOCTYPE html><title></title>');
	}
}

/**
 * Write a fixture download file under the repository, padded to a deterministic size.
 *
 * $repoDir is an absolute path (either the arsrepo root, or JPATH_SITE itself for the one fixture
 * file that deliberately lives outside the repository — see the publicLink item below). $relativeName
 * is the path underneath it; it is created if missing, together with a Joomla-style index.html in
 * every directory strictly between JPATH_SITE and the file, protecting the tree the same way ARS's
 * own repositories are protected. The site root itself is left alone — it already has a real
 * index.php, and marking it as a protected leaf directory would be actively misleading.
 *
 * The filler is a repeating deterministic byte pattern, NOT random bytes — a re-run must produce a
 * byte-identical file with the same sha256, or "does the manifest still match the file on disk" stops
 * being a meaningful assertion.
 *
 * @return array{relative: string, absolute: string, size: int, sha256: string, sentinel: string}
 */
function writeRepositoryFile(string $repoDir, string $relativeName, string $sentinel, int $padToBytes): array
{
	$absolute = $repoDir . '/' . ltrim($relativeName, '/');
	$dir      = dirname($absolute);

	if (!is_dir($dir))
	{
		mkdir($dir, 0755, true);
	}

	for ($walk = $dir; strlen($walk) > strlen(JPATH_SITE); $walk = dirname($walk))
	{
		ensureIndexHtml($walk);
	}

	$line    = $sentinel . "\n";
	$padding = max(0, $padToBytes - strlen($line));
	$chunk   = str_repeat('ARS-E2E-FILLER-0123456789abcdef', 64);
	$filler  = '';

	while (strlen($filler) < $padding)
	{
		$filler .= $chunk;
	}

	$content = $line . substr($filler, 0, $padding);

	file_put_contents($absolute, $content);

	return [
		'relative' => ltrim(substr($absolute, strlen(JPATH_SITE)), '/'),
		'absolute' => $absolute,
		'size'     => strlen($content),
		'sha256'   => hash('sha256', $content),
		'sentinel' => $sentinel,
	];
}

// ---------------------------------------------------------------------------
// 1. Repository filesystem.
//
// Wiped and rebuilt on every run, so reset() is a genuine reset of the on-disk state, not just the
// database rows that point at it. Categories are created below with `directory` values that must
// already exist on disk at check() time, so this has to run first.
// ---------------------------------------------------------------------------
$repoRoot = JPATH_SITE . '/arsrepo';

if (is_dir($repoRoot))
{
	Folder::delete($repoRoot);
}

Folder::create($repoRoot);
ensureIndexHtml($repoRoot);

/**
 * A deterministic Bleeding Edge tree: two version folders, each holding one small file.
 *
 * BleedingedgeModel::scanCategory() walks the category's `directory` for immediate subdirectories
 * (versions) and, within each, for files (excluding CHANGELOG variants). It never creates the
 * #__ars_releases/#__ars_items rows itself here — that only happens when the front-end scans the
 * category — so this script's job is limited to giving it a stable filesystem to find.
 */
writeRepositoryFile($repoRoot, 'e2e-bleedingedge/1.0.0/payload.bin', 'ARS-E2E-SENTINEL-bleedingedge-1.0.0', 512);
writeRepositoryFile($repoRoot, 'e2e-bleedingedge/2.0.0/payload.bin', 'ARS-E2E-SENTINEL-bleedingedge-2.0.0', 512);

/**
 * The publicLink item points at http://web/e2e-link-target.txt, a plain file written into the site
 * root (NOT the repository — link items are not filesystem items). `web` only resolves inside the
 * compose network, so the redirect-based download tests (url_dl = temp/permanent) assert the
 * Location header without following it, while the proxied path (url_dl = proxy) is fetched
 * server-side, from inside the container, and genuinely round-trips through this file.
 */
$linkTargetFile = writeRepositoryFile(JPATH_SITE, 'e2e-link-target.txt', 'ARS-E2E-SENTINEL-linkTarget', 256);

// ---------------------------------------------------------------------------
// 2. User groups.
//
// All of them hang off Registered, so every provisioned account is a normal logged-in user plus
// exactly one distinguishing privilege set.
// ---------------------------------------------------------------------------
$registeredId = groupId('Registered');

$groups = [
	// core.manage plus full CRUD on com_ars; back-end and API login.
	'managers'    => ensureGroup('ARS Managers', $registeredId),
	// core.manage on the component, but create/edit granted PER CATEGORY only — the category-scoped
	// authorisation case, and the group commit 08e4a68b's regression is built around (see the
	// per-category rules below).
	'catManagers' => ensureGroup('ARS Category Managers', $registeredId),
	// Holds the ARS Subscribers view level and nothing else. No back-end privileges at all.
	'subscribers' => ensureGroup('ARS Subscribers', $registeredId),
	// Ordinary registered users holding NO ARS view level. The negative control.
	'clients'     => ensureGroup('ARS Clients', $registeredId),
];

// ---------------------------------------------------------------------------
// 3. View access levels.
// ---------------------------------------------------------------------------
$viewLevels = [
	// "clients" is absent on purpose: it is the group that must NEVER see anything gated by this
	// level, which is what makes it useful as a negative control.
	'subscribers' => ensureViewLevel(
		'ARS Subscribers',
		[$groups['managers'], $groups['catManagers'], $groups['subscribers']]
	),
	/**
	 * A view level NO ARS account holds — Super Users only.
	 *
	 * This exists so "must be filtered out by access level" assertions can be made against an account
	 * that provably lacks it. It specifically cannot be tested with a Super User: Super Users hold
	 * every view level, so a "this must be hidden from users without the Secret level" assertion made
	 * with a Super User account would pass whether the check under test were present or not. `manager`
	 * below is deliberately NOT a Super User for the same reason.
	 */
	'secret'      => ensureViewLevel('ARS Secret', [8]),
];

// ---------------------------------------------------------------------------
// 4. ARS categories.
//
// All share directory = 'arsrepo' except the Bleeding Edge one, which gets its own subfolder so its
// version-folder scan target does not collide with the flat file layout the other categories use.
// ---------------------------------------------------------------------------
$categories = [
	'public'           => ensureArsCategory([
		'title'             => 'E2E Public Downloads',
		'alias'             => 'e2e-public',
		'description'       => '',
		'type'              => 'normal',
		'directory'         => 'arsrepo',
		'access'            => 1,
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'published'         => 1,
		'language'          => '*',
	]),
	'restricted'       => ensureArsCategory([
		'title'             => 'E2E Subscriber Downloads',
		'alias'             => 'e2e-restricted',
		'description'       => '',
		'type'              => 'normal',
		'directory'         => 'arsrepo',
		'access'            => $viewLevels['subscribers'],
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'published'         => 1,
		'language'          => '*',
	]),
	// Same access level as 'restricted', but configured to redirect unauthorised visitors to the
	// login page rather than showing a plain 403 — the show_unauth_links / redirect_unauth pair
	// exercised the OTHER way round.
	'restrictedLinked' => ensureArsCategory([
		'title'             => 'E2E Subscriber Downloads (Linked)',
		'alias'             => 'e2e-restricted-linked',
		'description'       => '',
		'type'              => 'normal',
		'directory'         => 'arsrepo',
		'access'            => $viewLevels['subscribers'],
		'show_unauth_links' => 1,
		'redirect_unauth'   => 'index.php?option=com_users&view=login',
		'published'         => 1,
		'language'          => '*',
	]),
	'secret'           => ensureArsCategory([
		'title'             => 'E2E Secret Downloads',
		'alias'             => 'e2e-secret',
		'description'       => '',
		'type'              => 'normal',
		'directory'         => 'arsrepo',
		'access'            => $viewLevels['secret'],
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'published'         => 1,
		'language'          => '*',
	]),
	'unpublished'      => ensureArsCategory([
		'title'             => 'E2E Unpublished Downloads',
		'alias'             => 'e2e-unpublished',
		'description'       => '',
		'type'              => 'normal',
		'directory'         => 'arsrepo',
		'access'            => 1,
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'published'         => 0,
		'language'          => '*',
	]),
	'bleedingedge'     => ensureArsCategory([
		'title'             => 'E2E Bleeding Edge',
		'alias'             => 'e2e-bleedingedge',
		'description'       => '',
		'type'              => 'bleedingedge',
		'directory'         => 'arsrepo/e2e-bleedingedge',
		'access'            => 1,
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'published'         => 1,
		'language'          => '*',
	]),
];

// ---------------------------------------------------------------------------
// 5. ACL rules.
// ---------------------------------------------------------------------------
setAssetRules(
	'com_ars',
	[
		'core.manage'     => [$groups['managers'] => 1, $groups['catManagers'] => 1],
		'core.create'     => [$groups['managers'] => 1],
		'core.edit'       => [$groups['managers'] => 1],
		'core.edit.state' => [$groups['managers'] => 1],
		'core.delete'     => [$groups['managers'] => 1],
	]
);

setAssetRules(
	'com_ars.category.' . $categories['public'],
	[
		'core.create' => [$groups['catManagers'] => 1],
		'core.edit'   => [$groups['catManagers'] => 1],
	]
);

/**
 * Deliberately NOT core.create: catManager holds core.edit on this category but was never granted
 * core.create here. This is what makes the "a user with core.edit but not core.create must be
 * refused by save2copy" regression (commit 08e4a68b) assertable — without this asymmetry a
 * catManager who could always do both would never exercise the missing check.
 */
setAssetRules(
	'com_ars.category.' . $categories['restricted'],
	[
		'core.edit' => [$groups['catManagers'] => 1],
	]
);

// ---------------------------------------------------------------------------
// 6. Root-asset grants.
// ---------------------------------------------------------------------------
grantRootAction('core.login.admin', $groups['managers']);
grantRootAction('core.login.admin', $groups['catManagers']);

/**
 * core.login.api, granted to managers, catManagers, subscribers AND clients.
 *
 * The API-authorisation regression (commit 17d20fed) is about an authenticated but UNPRIVILEGED token
 * holder. If clients or subscribers could not log into the API at all, every such request would 401
 * before ARS's own authorisation logic ever ran, and the test could not tell a working authorisation
 * check from a merely missing login grant — the two failure modes look identical from the outside.
 *
 * catManagers are here for the other half of that commit: per-category create/edit gating on the
 * write endpoints. That account holds core.create on the public category and only core.edit on the
 * restricted one, which is the asymmetry the write tests turn on — and it is only observable over the
 * API if the account can authenticate against it.
 */
grantRootAction('core.login.api', $groups['managers']);
grantRootAction('core.login.api', $groups['catManagers']);
grantRootAction('core.login.api', $groups['subscribers']);
grantRootAction('core.login.api', $groups['clients']);

// ---------------------------------------------------------------------------
// 7. Component parameters.
//
// Pinned to their shipped defaults (per component/backend/config.xml) EXCEPT url_dl, allowcaching and
// use_compatibility, which are pinned to values the download tests specifically depend on. Pinning
// even the values that match the default means a future change to that default cannot silently
// change what these tests exercise.
// ---------------------------------------------------------------------------
setComponentParams(
	[
		'hitcounting'           => 0,
		'log'                   => 1,
		'url_dl'                => 'temp',
		'allowcaching'          => 0,
		'minify_xml'            => 1,
		'liar_mode'             => 1,
		'use_compatibility'     => 0,
		'content_digest'        => 1,
		'show_checksums'        => 0,
		'show_directlink'       => 1,
		'directlink_extensions' => 'zip,tar,tar.gz',
		'no_access_url'         => '',
	]
);

// ---------------------------------------------------------------------------
// 8. Users.
// ---------------------------------------------------------------------------
$users = [
	'manager'    => ensureUser('arsmanager', 'ARS E2E Manager', 'arsmanager@example.test', $password, [$registeredId, $groups['managers']]),
	'catManager' => ensureUser('arscatmanager', 'ARS E2E CatManager', 'arscatmanager@example.test', $password, [$registeredId, $groups['catManagers']]),
	'subscriber' => ensureUser('arssubscriber', 'ARS E2E Subscriber', 'arssubscriber@example.test', $password, [$registeredId, $groups['subscribers']]),
	'client'     => ensureUser('arsclient', 'ARS E2E Client', 'arsclient@example.test', $password, [$registeredId, $groups['clients']]),
	// A second, unrelated "clients" member, needed for the Download-ID-ownership assertions: proving
	// that client's Download ID cannot be used to authenticate as other, or vice versa.
	'other'      => ensureUser('arsother', 'ARS E2E Other', 'arsother@example.test', $password, [$registeredId, $groups['clients']]),
];

$adminUserId = (int) $db->setQuery(
	$db->createQuery()
		->select($db->quoteName('id'))
		->from($db->quoteName('#__users'))
		->where($db->quoteName('id') . ' IN (SELECT ' . $db->quoteName('user_id') . ' FROM ' . $db->quoteName('#__user_usergroup_map') . ' WHERE ' . $db->quoteName('group_id') . ' = 8)')
		->order($db->quoteName('id') . ' ASC')
		->setLimit(1)
)->loadResult();

// ---------------------------------------------------------------------------
// 9. Environments.
//
// The install seeds #__ars_environments with a long, static list that stops at Joomla 4.1. Ensure the
// two Joomla versions actually in this suite's test matrix, plus the PHP version, exist and record
// their ids — ensure-style, so ids stay stable across resets.
// ---------------------------------------------------------------------------
$environments = [
	'joomla54' => ensureEnvironment('Joomla! 5.4', 'joomla/5.4'),
	'joomla61' => ensureEnvironment('Joomla! 6.1', 'joomla/6.1'),
	'php84'    => ensureEnvironment('PHP 8.4', 'php/8.4'),
];

$envIds = array_values($environments);

// ---------------------------------------------------------------------------
// 10. Truncate the re-derived tables, then rebuild them.
//
// #__ars_categories and #__ars_environments are ensure-style (see above): they own #__assets rows
// and install-seeded data respectively, so they are never truncated and their ids stay stable across
// resets. Everything below IS truncated and reinserted on every run, which is what makes reset() a
// genuine reset rather than an accumulation of stale fixtures — a test that mutates a release or
// burns a Download ID can ask for a clean slate instead of cleaning up after itself.
//
// The consequence: release, item, update-stream, auto-description and Download-ID-label ids are
// REALLOCATED on every reset. Tests must always re-read them from the manifest, never hard-code them.
// ---------------------------------------------------------------------------
foreach (['#__ars_releases', '#__ars_items', '#__ars_log', '#__ars_updatestreams', '#__ars_autoitemdesc', '#__ars_dlidlabels'] as $table)
{
	$db->truncateTable($table);
}

// ---------------------------------------------------------------------------
// 11. Releases.
//
// `publicSecurity` at severity 3 and everything else at severity 0 is what makes the Joomla 6.2
// <security> element regression (commit 37ad5eed) assertable in BOTH directions: the element must be
// emitted when severity > 0 and omitted entirely — not emitted as <security>0</security> — when it
// is 0.
//
// `publicSubscriberOnly` sits in the (public, access=1) category but is itself gated to the
// Subscribers level, so release-level access can be proven independently of its category's access.
// ---------------------------------------------------------------------------
$releaseSpecs = [
	'publicStable'            => ['public', '1.0.0', 'stable', 0, 1, 1, 600],
	'publicSecurity'          => ['public', '1.1.0', 'stable', 3, 1, 1, 560],
	'publicBeta'              => ['public', '2.0.0.b1', 'beta', 0, 1, 1, 520],
	'publicAlpha'             => ['public', '2.0.0.a1', 'alpha', 0, 1, 1, 480],
	'publicUnpublished'       => ['public', '0.9.0', 'stable', 0, 1, 0, 440],
	'publicSubscriberOnly'    => ['public', '1.2.0', 'stable', 0, $viewLevels['subscribers'], 1, 400],
	'restrictedStable'        => ['restricted', '3.0.0', 'stable', 0, 1, 1, 360],
	'restrictedLinkedStable'  => ['restrictedLinked', '3.0.0', 'stable', 0, 1, 1, 320],
	'secretStable'            => ['secret', '4.0.0', 'stable', 0, 1, 1, 280],
	'unpublishedCatStable'    => ['unpublished', '5.0.0', 'stable', 0, 1, 1, 240],
];

$releases = [];

foreach ($releaseSpecs as $key => [$catKey, $version, $maturity, $security, $access, $published, $agoMinutes])
{
	$created = minutesAgo($agoMinutes);

	$table = storeTable(
		new ReleaseTable($db),
		[
			'id'                => 0,
			'category_id'       => $categories[$catKey],
			'version'           => $version,
			'alias'             => '',
			'maturity'          => $maturity,
			'security'          => $security,
			'notes'             => '<p>E2E release notes for ' . $key . '.</p>',
			'hits'              => 0,
			'created'           => $created,
			'created_by'        => $users['manager'],
			'modified'          => $created,
			'modified_by'       => $users['manager'],
			'access'            => $access,
			'show_unauth_links' => 0,
			'redirect_unauth'   => '',
			'published'         => $published,
			'language'          => '*',
		],
		sprintf('release "%s"', $key)
	);

	$releases[$key] = (int) $table->id;
}

// ---------------------------------------------------------------------------
// 12. Update streams.
//
// `unpublishedStream` is published = 0. ItemTable::getUpdateStream() does NOT filter by published
// when matching, so it exists purely to be asserted against directly (e.g. "an unpublished stream
// must not appear in the update XML"), not to interfere with item auto-matching.
// ---------------------------------------------------------------------------
$updateStreamSpecs = [
	'main'              => ['E2E Component', 'components', 'com_e2e', 'public', 'com_e2e-*', 1],
	'restrictedStream'  => ['E2E Restricted Component', 'components', 'com_e2e_sub', 'restricted', 'com_e2e_sub-*', 1],
	'unpublishedStream' => ['E2E Gone Component', 'components', 'com_e2e_gone', 'public', 'com_e2e_gone-*', 0],
];

$updateStreams = [];

foreach ($updateStreamSpecs as $key => [$name, $type, $element, $catKey, $packname, $published])
{
	$table = storeTable(
		new UpdatestreamTable($db),
		[
			'id'        => 0,
			'name'      => $name,
			'alias'     => '',
			'type'      => $type,
			'element'   => $element,
			'category'  => $categories[$catKey],
			'packname'  => $packname,
			'client_id' => 1,
			'folder'    => '',
			'published' => $published,
		],
		sprintf('update stream "%s"', $key)
	);

	$updateStreams[$key] = (int) $table->id;
}

// ---------------------------------------------------------------------------
// 13. Auto-descriptions.
//
// One row, for the public category, matching the 'com_e2e-*' packname — the same pattern the 'main'
// update stream uses. `publicFile` below is created with an empty title/description/environments so
// that ItemTable::applyAutoDescriptions() actually has something to fill in, and its effect can be
// observed in the stored row.
// ---------------------------------------------------------------------------
$autoDescriptionTable = storeTable(
	new AutodescriptionTable($db),
	[
		'id'                => 0,
		'category'          => $categories['public'],
		'packname'          => 'com_e2e-*',
		'title'             => 'E2E Auto-Description Title',
		'access'            => 0,
		'show_unauth_links' => 0,
		'redirect_unauth'   => '',
		'description'       => '<p>E2E auto-generated description.</p>',
		'environments'      => $envIds,
		'published'         => 1,
	],
	'auto-description "public"'
);

$autoDescriptions = ['public' => (int) $autoDescriptionTable->id];

// ---------------------------------------------------------------------------
// 14. Items.
//
// Every file-type item needs a real file on disk BEFORE the corresponding ItemTable::store() call:
// ItemTable::onBeforeCheck() resolves the file relative to the release's category `directory` and
// computes md5/sha1/sha256/sha384/sha512/filesize from it right there in check(). Writing the file
// after storing the row would leave those columns empty or, worse, computed from whatever happened to
// be on disk from a previous run.
//
// `publicFile` gets >= 3 MiB so the 1 MiB-chunked streaming path in ItemModel::downloadFileItem() and
// HTTP Range requests are genuinely exercised, not merely present in the code path. Every other file
// is small and deterministic.
// ---------------------------------------------------------------------------
$itemSpecs = [
	'publicFile'                => [
		'release' => 'publicStable', 'type' => 'file', 'filename' => 'com_e2e-1.0.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 3 * 1024 * 1024,
		// Left blank on purpose: applyAutoDescriptions() should fill these in from the 'public'
		// auto-description row created above.
		'title' => '', 'description' => '', 'environments' => [],
	],
	'publicLink'                => [
		'release' => 'publicStable', 'type' => 'link', 'filename' => '', 'url' => 'http://web/e2e-link-target.txt',
		'access' => 1, 'published' => 1, 'size' => null,
		'title' => 'E2E Public Link', 'description' => '<p>E2E link item.</p>', 'environments' => $envIds,
	],
	// Item-level ACL inside an otherwise public release: the release and category are both access=1,
	// but this item alone is gated to the Subscribers level.
	'publicSubscriberItem'      => [
		'release' => 'publicStable', 'type' => 'file', 'filename' => 'com_e2e_restricted-1.0.0.zip', 'url' => '',
		'access' => $viewLevels['subscribers'], 'published' => 1, 'size' => 4096,
		'title' => 'E2E Public Subscriber-Only Item', 'description' => '<p>E2E item-level ACL.</p>', 'environments' => $envIds,
	],
	'publicUnpublishedItem'     => [
		'release' => 'publicStable', 'type' => 'file', 'filename' => 'com_e2e_hidden-1.0.0.zip', 'url' => '',
		'access' => 1, 'published' => 0, 'size' => 4096,
		'title' => 'E2E Public Unpublished Item', 'description' => '<p>E2E unpublished item.</p>', 'environments' => $envIds,
	],
	'securityFile'              => [
		'release' => 'publicSecurity', 'type' => 'file', 'filename' => 'com_e2e-1.1.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Security Release File', 'description' => '<p>E2E security release.</p>', 'environments' => $envIds,
	],
	'betaFile'                  => [
		'release' => 'publicBeta', 'type' => 'file', 'filename' => 'com_e2e-2.0.0.b1.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Beta Release File', 'description' => '<p>E2E beta release.</p>', 'environments' => $envIds,
	],
	'alphaFile'                 => [
		'release' => 'publicAlpha', 'type' => 'file', 'filename' => 'com_e2e-2.0.0.a1.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Alpha Release File', 'description' => '<p>E2E alpha release.</p>', 'environments' => $envIds,
	],
	'unpublishedReleaseFile'    => [
		'release' => 'publicUnpublished', 'type' => 'file', 'filename' => 'com_e2e-0.9.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Unpublished-Release File', 'description' => '<p>E2E item in an unpublished release.</p>', 'environments' => $envIds,
	],
	'subscriberOnlyReleaseFile' => [
		'release' => 'publicSubscriberOnly', 'type' => 'file', 'filename' => 'com_e2e-1.2.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Subscriber-Only-Release File', 'description' => '<p>E2E item in a subscriber-only release.</p>', 'environments' => $envIds,
	],
	// The file that must never leak: reachable only through the Subscribers-gated 'restricted' category.
	'restrictedFile'            => [
		'release' => 'restrictedStable', 'type' => 'file', 'filename' => 'com_e2e_sub-3.0.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Restricted File', 'description' => '<p>E2E restricted file. Must never leak.</p>', 'environments' => $envIds,
	],
	'restrictedLinkedFile'      => [
		'release' => 'restrictedLinkedStable', 'type' => 'file', 'filename' => 'com_e2e_sub-3.0.0-linked.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Restricted Linked File', 'description' => '<p>E2E restricted-linked file.</p>', 'environments' => $envIds,
	],
	'secretFile'                => [
		'release' => 'secretStable', 'type' => 'file', 'filename' => 'com_e2e_secret-4.0.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Secret File', 'description' => '<p>E2E secret file.</p>', 'environments' => $envIds,
	],
	'unpublishedCatFile'        => [
		'release' => 'unpublishedCatStable', 'type' => 'file', 'filename' => 'com_e2e_unpub-5.0.0.zip', 'url' => '',
		'access' => 1, 'published' => 1, 'size' => 4096,
		'title' => 'E2E Unpublished-Category File', 'description' => '<p>E2E item in an unpublished category.</p>', 'environments' => $envIds,
	],
];

$items = [];
$files = [];

foreach ($itemSpecs as $key => $spec)
{
	if ($spec['type'] === 'file')
	{
		// Write the file to disk BEFORE storing the item row — see the section comment above.
		$files[$key] = writeRepositoryFile($repoRoot, $spec['filename'], 'ARS-E2E-SENTINEL-' . $key, $spec['size']);
	}

	$table = storeTable(
		new ItemTable($db),
		[
			'id'                => 0,
			'release_id'        => $releases[$spec['release']],
			'title'             => $spec['title'],
			'alias'             => '',
			'description'       => $spec['description'],
			'type'              => $spec['type'],
			'filename'          => $spec['filename'],
			'url'               => $spec['url'],
			'hits'              => 0,
			'access'            => $spec['access'],
			'show_unauth_links' => 0,
			'redirect_unauth'   => '',
			'published'         => $spec['published'],
			'language'          => '*',
			'environments'      => $spec['environments'],
		],
		sprintf('item "%s"', $key)
	);

	$items[$key] = (int) $table->id;
}

$files['linkTarget'] = $linkTargetFile;

// ---------------------------------------------------------------------------
// 15. Download ID labels.
//
// DlidlabelTable::onBeforeCheck() computes `primary` and `published` itself: the FIRST row stored for
// a given user_id becomes primary (published forced to 1, title forced to '_MAIN_'); every subsequent
// row for that user is forced secondary, and only then does the `published` value we pass through
// actually take effect. Creation order therefore decides which row ends up primary — that is why
// subscriberPrimary is created before subscriberSecondary and subscriberRevoked.
//
// subscriberRevoked (published = 0) exists so "an unpublished Download ID must not authenticate" is
// assertable. clientPrimary exists so "a valid Download ID belonging to an account without the
// required view level must still be refused the file" is assertable — authentication (is this a real,
// active Download ID?) and authorisation (does its owner have access to THIS item?) are different
// questions, and this is what keeps the fixture from conflating them.
// ---------------------------------------------------------------------------
$dlidLabelSpecs = [
	'subscriberPrimary'   => ['subscriber', 1, 'subscriber'],
	'subscriberSecondary' => ['subscriber', 1, 'subscriberSecondary'],
	'subscriberRevoked'   => ['subscriber', 0, 'subscriberRevoked'],
	'clientPrimary'       => ['client', 1, 'client'],
	'otherPrimary'        => ['other', 1, 'other'],
];

$dlidLabels = [];
$dlids      = [];

foreach ($dlidLabelSpecs as $key => [$userKey, $published, $manifestKey])
{
	$table = storeTable(
		new DlidlabelTable($db),
		[
			'id'        => 0,
			'user_id'   => $users[$userKey],
			'title'     => 'E2E ' . $key,
			'dlid'      => null,
			'published' => $published,
		],
		sprintf('Download ID label "%s"', $key)
	);

	$dlidLabels[$key]    = (int) $table->id;
	$dlids[$manifestKey] = (string) $table->dlid;
}

// ---------------------------------------------------------------------------
// 16. Plugins and API tokens.
// ---------------------------------------------------------------------------
enablePlugin('api-authentication', 'token');
enablePlugin('webservices', 'ars');
enablePlugin('content', 'arsdlid');
enablePlugin('content', 'arslatest');

/**
 * plg_user_token's allowedUserGroups, which decides who may authenticate against the API at all.
 *
 * This has to be set explicitly and cannot be left at the shipped `{}`. Verified in the Joomla source
 * rather than assumed: plg_api-authentication_token reads the setting from THIS plugin, via
 * getPluginParameter('user', 'token', 'allowedUserGroups', [8]) — so an absent key does not mean "no
 * restriction", it means "Super Users only". Leaving it alone produces a 401 for every non-Super-User
 * token, which looks exactly like a broken token and is a genuinely nasty thing to debug.
 */
enablePlugin(
	'user',
	'token',
	[
		'allowedUserGroups'  => [
			8,
			$groups['managers'],
			$groups['catManagers'],
			$groups['subscribers'],
			$groups['clients'],
		],
		'saveTokenInProfile' => 1,
	]
);

$apiTokens = [
	'manager'    => issueApiToken($users['manager']),
	// The per-category write asymmetry (core.create on public, core.edit only on restricted) is only
	// assertable over the API if this account has a token of its own.
	'catManager' => issueApiToken($users['catManager']),
	'subscriber' => issueApiToken($users['subscriber']),
	'client'     => issueApiToken($users['client']),
	'superuser'  => issueApiToken($adminUserId),
];

// ---------------------------------------------------------------------------
// 17. Manifest.
// ---------------------------------------------------------------------------

// Joomla caches ACL aggressively in-process; clear it before reading anything back so the manifest
// reflects the rules actually on disk, not a stale in-memory copy from earlier in this script.
Access::clearStatics();

$manifest = [
	'generated'        => Factory::getDate()->toSql(),
	'joomla'           => JVERSION,
	'groups'           => $groups,
	'registeredGroup'  => $registeredId,
	'viewLevels'       => $viewLevels,
	'users'            => $users + ['superuser' => $adminUserId],
	'usernames'        => [
		'manager'    => 'arsmanager',
		'catManager' => 'arscatmanager',
		'subscriber' => 'arssubscriber',
		'client'     => 'arsclient',
		'other'      => 'arsother',
	],
	'apiTokens'        => $apiTokens,
	'categories'       => $categories,
	'releases'         => $releases,
	'items'            => $items,
	'updateStreams'    => $updateStreams,
	'autoDescriptions' => $autoDescriptions,
	'environments'     => $environments,
	'dlidLabels'       => $dlidLabels,
	'dlids'            => $dlids,
	'files'            => $files,
	'repository'       => [
		'relative' => 'arsrepo',
		'absolute' => $repoRoot,
	],
];

file_put_contents(JPATH_SITE . '/e2e-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

echo json_encode($manifest, JSON_PRETTY_PRINT) . "\n";
