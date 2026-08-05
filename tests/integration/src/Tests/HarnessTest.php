<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use PHPUnit\Framework\Attributes\Group;

/**
 * Does the harness itself work?
 *
 * Nothing here tests ARS. Every assertion is about the machinery the other tests stand on, and each
 * one exists because if it were quietly false, some other test would go green while proving nothing.
 *
 * The last two are the important ones. A fixture that accidentally granted the `client` account the
 * ARS Subscribers view level, or a `restricted` category that was published with access 1 because a
 * column name was wrong, would turn the whole download-authorisation suite into a test of nothing —
 * and it would still be green. So the fixtures are asserted against the site's own idea of them,
 * not against the manifest that created them.
 *
 * When this class fails, fix it before believing anything else in the suite.
 *
 * @since 7.5.0
 */
#[Group('harness')]
class HarnessTest extends AbstractE2ETestCase
{
	/**
	 * The site answers, and ARS is installed on it.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSiteIsUpAndArsIsInstalled(): void
	{
		// The layout is explicit. ARS's front-end category view has no `default` layout — the
		// available ones are normal, repository and bleedingedge — and omitting it is its own
		// (separate, real) problem; see CategoriesLayoutTest. Leaving it out here would make this
		// check fail for a reason that has nothing to do with whether the harness works.
		$response = $this->guest()->get($this->siteUrl(['view' => 'categories', 'layout' => 'repository']));

		$this->assertStatus(200, $response, 'The ARS front-end category list did not render.');

		$installed = (int) $this->db()->value(
			'SELECT COUNT(*) FROM `#__extensions` WHERE `element` = ? AND `type` = ?',
			['com_ars', 'component']
		);

		$this->assertSame(1, $installed, 'com_ars is not registered in #__extensions.');

		$enabled = (int) $this->db()->value(
			'SELECT `enabled` FROM `#__extensions` WHERE `element` = ? AND `type` = ?',
			['com_ars', 'component']
		);

		$this->assertSame(1, $enabled, 'com_ars is installed but disabled.');
	}

	/**
	 * The ARS schema is actually there.
	 *
	 * A component whose install SQL half-ran would still answer the check above.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testArsSchemaExists(): void
	{
		$tables = [
			'#__ars_categories',
			'#__ars_releases',
			'#__ars_items',
			'#__ars_log',
			'#__ars_updatestreams',
			'#__ars_autoitemdesc',
			'#__ars_environments',
			'#__ars_dlidlabels',
		];

		foreach ($tables as $table)
		{
			$rows = $this->db()->all('SHOW TABLES LIKE ' . $this->db()->getPdo()->quote($this->db()->prefix($table)));

			$this->assertCount(1, $rows, sprintf('The %s table is missing.', $table));
		}

		// The Joomla 6.2 security severity column, added in 7.5.0. Its absence would make every
		// update-stream <security> assertion pass vacuously.
		$columns = $this->db()->all('SHOW COLUMNS FROM `' . $this->db()->prefix('#__ars_releases') . '` LIKE ' . $this->db()->getPdo()->quote('security'));

		$this->assertCount(1, $columns, 'The #__ars_releases.security column is missing; the schema is out of date.');
	}

	/**
	 * The outbound mail sink answers.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testMailpitIsReachable(): void
	{
		$this->assertTrue($this->mailpit()->isAvailable(), 'Mailpit did not answer its info endpoint.');

		$this->mailpit()->clear();

		$this->assertSame(0, $this->mailpit()->count(), 'Mailpit did not clear.');
	}

	/**
	 * A front-end login produces a real session, and a fresh surfer does not.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testFrontendLoginEstablishesASession(): void
	{
		$subscriber = $this->loggedIn('subscriber');

		$this->assertSame(
			static::$fixtures->username('subscriber'),
			$this->session->getCurrentUsername($subscriber),
			'The site does not think the logged-in surfer is who it just logged in.'
		);

		$this->assertNull(
			$this->session->getCurrentUsername($this->guest()),
			'A surfer that never logged in is being reported as somebody.'
		);
	}

	/**
	 * A back-end login reaches an ARS admin view.
	 *
	 * Deliberately NOT `view=controlpanel`. That view's default task is `main`, which performs
	 * housekeeping writes (`saveMagicVariables()`, `adoptMyExtensions()`) and then redirects to
	 * com_cpanel — so using it here would give every run of this suite a side effect on the site,
	 * and would conflate "is the session real?" with "does the housekeeping work?". The latter is a
	 * question of its own, and it has its own test.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testBackendLoginReachesAnArsAdminView(): void
	{
		$admin = $this->superUser();

		$this->assertTrue(
			$this->session->isLoggedInBackend($admin),
			'The Super User did not end up logged into the back-end.'
		);

		$admin->followRedirects = true;

		try
		{
			$response = $admin->get($this->adminUrl(['view' => 'categories']));
		}
		finally
		{
			$admin->followRedirects = false;
		}

		$this->assertStatus(200, $response, 'The ARS category manager did not render for a Super User.');
		$this->assertBodyNotContains(
			'name="passwd"',
			$response,
			'The back-end handed back the login form, so the session did not stick.'
		);
	}

	/**
	 * The ACL matrix grants exactly what the fixtures claim it grants.
	 *
	 * Read through the site's own `User::authorise()` and `getAuthorisedViewLevels()`, because that
	 * is what ARS consults. Every asymmetry below is load-bearing somewhere else in the suite; the
	 * comment on each says where.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testAclMatrixIsWhatItClaimsToBe(): void
	{
		$publicCategoryAsset     = 'com_ars.category.' . static::$fixtures->categoryId('public');
		$restrictedCategoryAsset = 'com_ars.category.' . static::$fixtures->categoryId('restricted');

		$assets  = ['com_ars', $publicCategoryAsset, $restrictedCategoryAsset];
		$actions = ['core.admin', 'core.manage', 'core.create', 'core.edit', 'core.delete'];

		$subscribersLevel = static::$fixtures->viewLevelId('subscribers');
		$secretLevel      = static::$fixtures->viewLevelId('secret');

		// -- manager -----------------------------------------------------------------------------
		$manager = $this->session->probeIdentity($this->loggedIn('manager'), $assets, $actions);

		$this->assertIsArray($manager, 'The probe did not answer for the manager account.');
		$this->assertTrue($manager['authorise']['com_ars']['core.manage'], 'The manager cannot manage com_ars.');
		$this->assertFalse(
			$manager['authorise']['com_ars']['core.admin'],
			'The manager holds core.admin. It must not: a Super User is granted every view level, so any '
			. '"this must be filtered out by access level" assertion made with such an account proves nothing.'
		);
		$this->assertContains(
			$subscribersLevel,
			$manager['viewLevels'],
			'The manager does not hold the ARS Subscribers view level.'
		);
		$this->assertNotContains(
			$secretLevel,
			$manager['viewLevels'],
			'The manager holds the ARS Secret view level. That level exists precisely so a non-Super-User '
			. 'provably lacks one, which is what makes the secret-category tests meaningful.'
		);

		// -- catManager --------------------------------------------------------------------------
		$catManager = $this->session->probeIdentity($this->loggedIn('catManager'), $assets, $actions);

		$this->assertIsArray($catManager, 'The probe did not answer for the catManager account.');
		$this->assertTrue(
			$catManager['authorise']['com_ars']['core.manage'],
			'The catManager cannot manage com_ars, so it cannot reach the views its tests exercise.'
		);
		$this->assertFalse(
			$catManager['authorise']['com_ars']['core.create'],
			'The catManager holds core.create at component level. It must not — the save2copy regression '
			. 'turns on an account that may edit but may not create.'
		);
		$this->assertTrue(
			$catManager['authorise'][$publicCategoryAsset]['core.create'],
			'The catManager cannot create in the public category.'
		);
		$this->assertTrue(
			$catManager['authorise'][$restrictedCategoryAsset]['core.edit'],
			'The catManager cannot edit in the restricted category.'
		);
		$this->assertFalse(
			$catManager['authorise'][$restrictedCategoryAsset]['core.create'],
			'The catManager can create in the restricted category. That asymmetry is the whole point of '
			. 'this account: core.edit without core.create, in one specific category.'
		);

		// -- subscriber --------------------------------------------------------------------------
		$subscriber = $this->session->probeIdentity($this->loggedIn('subscriber'), $assets, $actions);

		$this->assertIsArray($subscriber, 'The probe did not answer for the subscriber account.');
		$this->assertFalse($subscriber['authorise']['com_ars']['core.manage'], 'The subscriber can manage com_ars.');
		$this->assertContains(
			$subscribersLevel,
			$subscriber['viewLevels'],
			'The subscriber does not hold the ARS Subscribers view level, so every "a subscriber CAN '
			. 'download this" assertion would fail for the wrong reason.'
		);

		// -- client ------------------------------------------------------------------------------
		$client = $this->session->probeIdentity($this->loggedIn('client'), $assets, $actions);

		$this->assertIsArray($client, 'The probe did not answer for the client account.');
		$this->assertNotContains(
			$subscribersLevel,
			$client['viewLevels'],
			'The client holds the ARS Subscribers view level. It is the negative control for every '
			. 'download-authorisation test; if it can see subscriber content, none of them mean anything.'
		);
		$this->assertFalse($client['authorise']['com_ars']['core.manage'], 'The client can manage com_ars.');

		// -- guest -------------------------------------------------------------------------------
		$guest = $this->session->probeIdentity($this->guest(), $assets, $actions);

		$this->assertIsArray($guest, 'The probe did not answer for a guest.');
		$this->assertTrue($guest['guest'], 'The probe does not consider a fresh surfer a guest.');
		$this->assertSame(
			[1],
			array_values(array_intersect([1], $guest['viewLevels'])),
			'A guest does not hold the Public view level, which would break every baseline assertion.'
		);
		$this->assertNotContains($subscribersLevel, $guest['viewLevels'], 'A guest holds the ARS Subscribers view level.');
	}

	/**
	 * The ARS fixtures are on the site, with the access and published flags the tests assume.
	 *
	 * Asserted against the database rather than against the manifest: the manifest is what the
	 * provisioner meant to create, and this is what it actually created.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testCategoryFixturesHaveTheAccessTheyClaim(): void
	{
		$subscribersLevel = static::$fixtures->viewLevelId('subscribers');
		$secretLevel      = static::$fixtures->viewLevelId('secret');

		$expected = [
			// key                  access             published  show_unauth_links
			'public'           => [1,                 1,         0],
			'restricted'       => [$subscribersLevel, 1,         0],
			'restrictedLinked' => [$subscribersLevel, 1,         1],
			'secret'           => [$secretLevel,      1,         0],
			'unpublished'      => [1,                 0,         0],
		];

		foreach ($expected as $key => [$access, $published, $showUnauthLinks])
		{
			$row = $this->db()->row(
				'SELECT `access`, `published`, `show_unauth_links` FROM `#__ars_categories` WHERE `id` = ?',
				[static::$fixtures->categoryId($key)]
			);

			$this->assertIsArray($row, sprintf('The "%s" category is in the manifest but not in the database.', $key));
			$this->assertSame($access, (int) $row['access'], sprintf('The "%s" category has the wrong access level.', $key));
			$this->assertSame($published, (int) $row['published'], sprintf('The "%s" category has the wrong published state.', $key));
			$this->assertSame(
				$showUnauthLinks,
				(int) $row['show_unauth_links'],
				sprintf('The "%s" category has the wrong show_unauth_links flag.', $key)
			);
		}
	}

	/**
	 * Exactly one release carries a non-zero security severity.
	 *
	 * The Joomla 6.2 `<security>` element must be emitted above zero and omitted entirely at zero.
	 * Testing both halves needs both kinds of release to exist, and needs the zero ones to really be
	 * zero rather than merely unset.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testExactlyOneReleaseCarriesASecuritySeverity(): void
	{
		$severity = (int) $this->db()->value(
			'SELECT `security` FROM `#__ars_releases` WHERE `id` = ?',
			[static::$fixtures->releaseId('publicSecurity')]
		);

		$this->assertSame(3, $severity, 'The publicSecurity release does not carry severity 3.');

		$others = (int) $this->db()->value(
			'SELECT COUNT(*) FROM `#__ars_releases` WHERE `security` <> 0 AND `id` <> ?',
			[static::$fixtures->releaseId('publicSecurity')]
		);

		$this->assertSame(
			0,
			$others,
			'Another release carries a security severity, so "the element is omitted at zero" cannot be '
			. 'told apart from "the element is missing".'
		);
	}

	/**
	 * The release files really are on disk, with the contents the manifest recorded.
	 *
	 * The leak assertions elsewhere search a response body for a sentinel string. If the file were
	 * empty, or the sentinel were not in it, those assertions would pass on any response at all.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRepositoryFilesExistWithTheRecordedContents(): void
	{
		$root = static::$config->getSiteRoot();

		foreach (['publicFile', 'restrictedFile', 'secretFile'] as $key)
		{
			$file = static::$fixtures->file($key);
			$path = $root . '/' . ltrim($file['relative'], '/');

			$this->assertFileExists($path, sprintf('The "%s" release file is not on disk.', $key));
			$this->assertSame(
				$file['sha256'],
				hash_file('sha256', $path),
				sprintf('The "%s" release file on disk is not the one the manifest describes.', $key)
			);

			$this->assertStringContainsString(
				$file['sentinel'],
				(string) file_get_contents($path, false, null, 0, 4096),
				sprintf(
					'The "%s" release file does not carry its sentinel string, so a leak assertion against it '
					. 'would pass whether or not the file leaked.',
					$key
				)
			);
		}

		// One file has to span more than one of ItemModel's 1 MiB read chunks, so the streaming loop
		// and HTTP Range handling are genuinely exercised rather than skipped over in a single pass.
		$this->assertGreaterThan(
			2 * 1024 * 1024,
			(int) static::$fixtures->file('publicFile')['size'],
			'The publicFile fixture is too small to exercise chunked streaming or a partial fetch.'
		);
	}

	/**
	 * Every provisioned Download ID is well-formed and distinct.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testDownloadIdsAreWellFormedAndDistinct(): void
	{
		$roles = ['subscriber', 'subscriberSecondary', 'subscriberRevoked', 'client', 'other'];
		$seen  = [];

		foreach ($roles as $role)
		{
			$dlid = static::$fixtures->dlid($role);

			$this->assertMatchesRegularExpression(
				'/^[0-9a-f]{32}$/',
				$dlid,
				sprintf('The "%s" Download ID is not 32 lower-case hex characters.', $role)
			);

			$seen[] = $dlid;
		}

		$this->assertCount(
			count($roles),
			array_unique($seen),
			'Two provisioned Download IDs are identical, so a test could authenticate as the wrong account.'
		);

		// The revoked one must really be unpublished, or "an unpublished Download ID does not
		// authenticate" would be asserting nothing.
		$published = (int) $this->db()->value(
			'SELECT `published` FROM `#__ars_dlidlabels` WHERE `dlid` = ?',
			[static::$fixtures->dlid('subscriberRevoked')]
		);

		$this->assertSame(0, $published, 'The "revoked" Download ID is published.');
	}

	/**
	 * The API is reachable and its authentication chain is wired up.
	 *
	 * Four separate things have to be true for a Joomla API token to work, and when any one is
	 * missing the symptom is a 401 that looks exactly like a broken token. Establishing here that a
	 * privileged token gets 200 and no token gets 401 is what lets the authorisation tests read a
	 * 403 as ARS's answer rather than as plumbing.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testApiAuthenticationIsWiredUp(): void
	{
		$surfer  = new Surfer(static::$config->getSiteUrl());
		$headers = ['Accept' => 'application/vnd.api+json'];

		$anonymous = $surfer->request('GET', $this->apiUrl('v1/ars/categories'), null, $headers);

		$this->assertStatus(401, $anonymous, 'An unauthenticated API request was not refused with a 401.');

		$authenticated = $surfer->request(
			'GET',
			$this->apiUrl('v1/ars/categories'),
			null,
			$headers + ['X-Joomla-Token' => static::$fixtures->apiToken('manager')]
		);

		$this->assertStatus(
			200,
			$authenticated,
			'A manager token did not get through. Check that plg_api-authentication_token and '
			. 'plg_webservices_ars are enabled, that plg_user_token lists the manager group in '
			. 'allowedUserGroups, and that core.login.api is granted on the root asset.'
		);
	}

	/**
	 * An unprivileged account can authenticate against the API, but is not thereby authorised.
	 *
	 * This is the control for the API authorisation suite. If `client` could not log into the API at
	 * all, every one of those tests would see a 401 and could not tell a working authorisation check
	 * from a missing one.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testAnUnprivilegedTokenAuthenticatesButIsNotAuthorised(): void
	{
		$surfer = new Surfer(static::$config->getSiteUrl());

		$response = $surfer->request(
			'GET',
			$this->apiUrl('v1/ars/categories'),
			null,
			[
				'Accept'         => 'application/vnd.api+json',
				'X-Joomla-Token' => static::$fixtures->apiToken('client'),
			]
		);

		$this->assertNotSame(
			401,
			$response->code,
			'The client token could not authenticate at all. Grant core.login.api to its group, otherwise '
			. 'the API authorisation tests cannot distinguish a refusal from a login failure.'
		);

		$this->assertSame(
			403,
			$response->code,
			'An authenticated but unprivileged token was not refused with a 403.'
		);
	}
}
