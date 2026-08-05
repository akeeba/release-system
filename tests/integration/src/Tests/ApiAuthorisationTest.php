<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Response;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use Akeeba\ARS\IntegrationTest\SiteProvisioner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression for commit 17d20fed, "Hardening: enforce authorisation on the JSON:API endpoints".
 *
 * Before that fix the API application reused the Administrator (back-end) models, which do not
 * restrict their result set to the caller's authorised view levels — an administrator is expected to
 * see everything. Combined with the JSON:API views exposing file names, direct URLs and every
 * checksum, an authenticated-but-unprivileged token holder could enumerate access-restricted
 * downloads and write outside their per-category remit. AssertApiAccess::assertCanManage() now gates
 * every read behind core.manage, and allowAdd()/allowEdit() on each write controller re-check the
 * per-category core.create / core.edit permissions the back-end already enforced.
 *
 * @since 7.5.0
 */
#[Group('api')]
#[Group('authorisation')]
class ApiAuthorisationTest extends AbstractE2ETestCase
{
	/**
	 * Marker prefix for every throwaway row this class creates, so tearDown() can find and remove
	 * exactly those rows without touching a fixture.
	 *
	 * @since 7.5.0
	 */
	private const MARKER = 'E2E-WRITE-TEST';

	/**
	 * Absolute paths of any throwaway files this test placed on disk, so tearDown() can remove them
	 * even if the test that created one failed before its own cleanup ran.
	 *
	 * @var   string[]
	 * @since 7.5.0
	 */
	private array $filesToRemove = [];

	protected function tearDown(): void
	{
		$this->db()->query('DELETE FROM `#__ars_items` WHERE title LIKE ?', [self::MARKER . '%']);
		$this->db()->query('DELETE FROM `#__ars_releases` WHERE version LIKE ?', [self::MARKER . '%']);

		foreach ($this->filesToRemove as $path)
		{
			if (is_file($path))
			{
				@unlink($path);
			}
		}

		$this->filesToRemove = [];

		parent::tearDown();
	}

	/**
	 * Every read endpoint refuses a token that authenticates but holds no core.manage, and a manager
	 * token succeeds — proving the 403s above are refusing a real, reachable resource rather than
	 * failing for an unrelated reason.
	 *
	 * @since 7.5.0
	 */
	#[DataProvider('readEndpointProvider')]
	public function testReadEndpointsRefuseUnprivilegedTokens(string $path): void
	{
		foreach (['client', 'subscriber'] as $role)
		{
			$response = $this->api('GET', $path, static::$fixtures->apiToken($role));

			$this->assertStatus(
				403,
				$response,
				sprintf('A %s token (authenticated, no core.manage) was able to GET %s.', $role, $path)
			);
		}

		$response = $this->api('GET', $path, static::$fixtures->apiToken('manager'));

		$this->assertStatus(
			200,
			$response,
			sprintf('A manager token could not GET %s, so the 403s above do not prove anything.', $path)
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 * @since  7.5.0
	 */
	public static function readEndpointProvider(): array
	{
		$fixtures = SiteProvisioner::getInstance();

		return [
			'categories list'  => ['v1/ars/categories'],
			'releases list'    => ['v1/ars/releases'],
			'items list'       => ['v1/ars/items'],
			'single category'  => ['v1/ars/categories/' . $fixtures->categoryId('restricted')],
			'single release'   => ['v1/ars/releases/' . $fixtures->releaseId('restrictedStable')],
			'single item'      => ['v1/ars/items/' . $fixtures->itemId('restrictedFile')],
		];
	}

	/**
	 * The disclosure that motivated the fix: a privileged token really does get back the filename, every
	 * checksum and the access level of an access-restricted item. Without this the 403s above would be
	 * "protecting" a resource that might not carry anything worth protecting.
	 *
	 * @since 7.5.0
	 */
	public function testManagerTokenSeesTheDisclosedFields(): void
	{
		$itemId   = static::$fixtures->itemId('restrictedFile');
		$response = $this->api('GET', 'v1/ars/items/' . $itemId, static::$fixtures->apiToken('manager'));

		$this->assertStatus(200, $response, 'A manager token could not GET the restricted-category item.');

		$attributes = (array) ($response->json()['data']['attributes'] ?? []);

		$this->assertSame(
			'com_e2e_sub-3.0.0.zip',
			$attributes['filename'] ?? null,
			'The item payload does not disclose the filename, so a 403 on it would not be protecting anything.'
		);
		$this->assertArrayHasKey(
			'sha256',
			$attributes,
			'The item payload does not disclose the sha256 checksum.'
		);
		$this->assertNotEmpty(
			$attributes['sha256'] ?? '',
			'The disclosed sha256 checksum is empty.'
		);
		$this->assertArrayHasKey(
			'access',
			$attributes,
			'The item payload does not disclose the access level.'
		);
	}

	/**
	 * A token with no core.manage cannot create a release or an item over the API, and no row is
	 * created — the message alone would not prove the write never happened.
	 *
	 * @since 7.5.0
	 */
	public function testWriteEndpointsRefuseUnprivilegedTokens(): void
	{
		$releaseVersion = self::MARKER . ' client release';
		$releaseBody    = [
			'category_id' => static::$fixtures->categoryId('public'),
			'version'     => $releaseVersion,
			'alias'       => 'e2e-write-test-client-release',
			'maturity'    => 'stable',
		];

		$response = $this->api('POST', 'v1/ars/releases', static::$fixtures->apiToken('client'), $releaseBody);

		$this->assertStatus(403, $response, 'A client token was able to POST a new release.');
		$this->assertSame(
			0,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE version = ?', [$releaseVersion]),
			'A release row was created despite the 403.'
		);

		$itemTitle = self::MARKER . ' client item';
		$itemBody  = [
			'release_id' => static::$fixtures->releaseId('publicStable'),
			'type'       => 'link',
			'url'        => 'https://example.test/e2e-write-test',
			'title'      => $itemTitle,
		];

		$response = $this->api('POST', 'v1/ars/items', static::$fixtures->apiToken('client'), $itemBody);

		$this->assertStatus(403, $response, 'A client token was able to POST a new item.');
		$this->assertSame(
			0,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_items` WHERE title = ?', [$itemTitle]),
			'An item row was created despite the 403.'
		);
	}

	/**
	 * Per-category write authorisation: create is allowed where the account holds core.create, and
	 * refused where it does not — the second half of commit 17d20fed.
	 *
	 * `catManager` is the account this turns on. It holds core.manage on the component (so it gets
	 * past AssertApiAccess::assertCanManage() and any 403 below is genuinely about the category), and
	 * core.create on the `public` category only; on `restricted` it holds core.edit but deliberately
	 * NOT core.create. Both halves are asserted here, because a refusal test whose permitted twin is
	 * never checked would pass just as happily against an endpoint that refuses everybody.
	 *
	 * @since 7.5.0
	 */
	public function testPerCategoryCreateIsAllowedWhereGrantedAndRefusedWhereNot(): void
	{
		$token = static::$fixtures->apiToken('catManager');

		// The category it may create in.
		$allowedVersion = self::MARKER . ' catmanager-allowed ' . uniqid();
		$allowed        = $this->api(
			'POST',
			'v1/ars/releases',
			$token,
			[
				'category_id' => static::$fixtures->categoryId('public'),
				'version'     => $allowedVersion,
				'alias'       => 'e2e-write-test-catmanager-allowed-' . uniqid(),
				'maturity'    => 'stable',
				'published'   => 1,
			]
		);

		$this->assertStatus(
			200,
			$allowed,
			'The catManager account holds core.create on the public category but was refused a release there.'
		);
		$this->assertSame(
			1,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE version = ?', [$allowedVersion]),
			'The API answered 200 but no release row was created.'
		);

		// The category it may edit but not create in.
		$refusedVersion = self::MARKER . ' catmanager-refused ' . uniqid();
		$refused        = $this->api(
			'POST',
			'v1/ars/releases',
			$token,
			[
				'category_id' => static::$fixtures->categoryId('restricted'),
				'version'     => $refusedVersion,
				'alias'       => 'e2e-write-test-catmanager-refused-' . uniqid(),
				'maturity'    => 'stable',
				'published'   => 1,
			]
		);

		$this->assertStatus(
			403,
			$refused,
			'The catManager account holds core.edit but NOT core.create on the restricted category, '
			. 'yet the API let it create a release there.'
		);
		$this->assertSame(
			0,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE version = ?', [$refusedVersion]),
			'A release row was created in the restricted category despite the 403.'
		);
	}

	/**
	 * A token with no core.manage cannot delete a release, and the row survives.
	 *
	 * @since 7.5.0
	 */
	public function testDeleteRefusesUnprivilegedTokenAndRowSurvives(): void
	{
		$releaseId = $this->createThrowawayRelease('delete');

		$response = $this->api('DELETE', 'v1/ars/releases/' . $releaseId, static::$fixtures->apiToken('client'));

		$this->assertStatus(403, $response, 'A client token was able to DELETE a release.');
		$this->assertSame(
			1,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE id = ?', [$releaseId]),
			'The release row is gone despite the 403.'
		);

		// Prove the endpoint really does delete when the caller is privileged, and clean up.
		$response = $this->api('DELETE', 'v1/ars/releases/' . $releaseId, static::$fixtures->apiToken('manager'));

		$this->assertStatus(204, $response, 'A manager token could not DELETE its own throwaway release.');
		$this->assertSame(
			0,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE id = ?', [$releaseId]),
			'The release row survived a manager-token DELETE.'
		);
	}

	/**
	 * ItemsController::delete() honours delete_file=1 and delete_empty_directory=1 by unlinking the
	 * release file from disk. An unprivileged token must not be able to trigger that: the row and the
	 * file must both survive.
	 *
	 * @since 7.5.0
	 */
	public function testItemDeleteRefusesUnprivilegedTokenAndFileSurvives(): void
	{
		$releaseId = $this->createThrowawayRelease('delfile');
		$fileName  = 'e2e-write-test-delfile.bin';
		$filePath  = static::$config->getSiteRoot() . '/' . static::$fixtures->repositoryPath() . '/' . $fileName;

		file_put_contents($filePath, 'ARS-E2E-WRITE-TEST-SENTINEL');
		$this->filesToRemove[] = $filePath;

		$itemBody = [
			'release_id' => $releaseId,
			'type'       => 'file',
			'filename'   => $fileName,
			'title'      => self::MARKER . ' delfile item',
		];

		$createResponse = $this->api('POST', 'v1/ars/items', static::$fixtures->apiToken('manager'), $itemBody);
		$this->assertStatus(200, $createResponse, 'A manager token could not create the throwaway item.');

		$itemId = (int) ($createResponse->json()['data']['id'] ?? 0);
		$this->assertGreaterThan(0, $itemId, 'The throwaway item was not created.');

		$response = $this->api(
			'DELETE',
			'v1/ars/items/' . $itemId . '?delete_file=1&delete_empty_directory=1',
			static::$fixtures->apiToken('client')
		);

		$this->assertStatus(403, $response, 'A client token was able to DELETE an item with delete_file=1.');
		$this->assertSame(
			1,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_items` WHERE id = ?', [$itemId]),
			'The item row is gone despite the 403.'
		);
		$this->assertFileExists(
			$filePath,
			'The release file was unlinked from disk by a request that should have been refused.'
		);

		// Prove the endpoint really does unlink the file for a privileged caller, and clean up.
		$response = $this->api(
			'DELETE',
			'v1/ars/items/' . $itemId . '?delete_file=1&delete_empty_directory=0',
			static::$fixtures->apiToken('manager')
		);

		$this->assertStatus(204, $response, 'A manager token could not DELETE its own throwaway item.');
		$this->assertFileDoesNotExist(
			$filePath,
			'A manager-token delete_file=1 delete did not unlink the file.'
		);

		// Already gone; do not try to unlink it again in tearDown().
		$this->filesToRemove = [];
	}

	/**
	 * The unauthenticated control: no token at all is refused with 401, distinct from the 403 an
	 * authenticated-but-unprivileged token receives above.
	 *
	 * @since 7.5.0
	 */
	#[DataProvider('readEndpointProvider')]
	public function testUnauthenticatedRequestsAreRefusedWithADistinctStatus(string $path): void
	{
		$response = $this->api('GET', $path, null);

		$this->assertStatus(
			401,
			$response,
			sprintf('An unauthenticated GET of %s was not refused with 401.', $path)
		);
	}

	/**
	 * Create a throwaway, published release in the public category, owned by this test.
	 *
	 * @param   string  $label  A short label folded into the version string, for readability in a
	 *                          failure message or a stray row.
	 *
	 * @return  int
	 * @since   7.5.0
	 */
	private function createThrowawayRelease(string $label): int
	{
		$body = [
			'category_id' => static::$fixtures->categoryId('public'),
			'version'     => self::MARKER . ' ' . $label . ' ' . uniqid(),
			'alias'       => 'e2e-write-test-' . $label . '-' . uniqid(),
			'maturity'    => 'stable',
			'published'   => 1,
		];

		$response = $this->api('POST', 'v1/ars/releases', static::$fixtures->apiToken('manager'), $body);

		$this->assertStatus(200, $response, 'Could not create the throwaway release fixture for this test.');

		$id = (int) ($response->json()['data']['id'] ?? 0);

		$this->assertGreaterThan(0, $id, 'The throwaway release was not created.');

		return $id;
	}

	/**
	 * One small helper for a raw JSON:API request, per the task's convention: a fresh Surfer, no
	 * cookies, the vnd.api+json Accept header, and the token (if any) on X-Joomla-Token.
	 *
	 * @param   string       $verb   HTTP verb.
	 * @param   string       $path   Path under api/index.php, e.g. 'v1/ars/releases'.
	 * @param   string|null  $token  A Joomla API token, or null for an unauthenticated request.
	 * @param   array|null   $body   Request body, JSON-encoded. Null for no body.
	 *
	 * @return  Response
	 * @since   7.5.0
	 */
	private function api(string $verb, string $path, ?string $token, ?array $body = null): Response
	{
		$surfer  = new Surfer(static::$config->getSiteUrl());
		$headers = ['Accept' => 'application/vnd.api+json'];

		if ($token !== null)
		{
			$headers['X-Joomla-Token'] = $token;
		}

		if ($body !== null)
		{
			$headers['Content-Type'] = 'application/json';

			return $surfer->request($verb, $this->apiUrl($path), json_encode($body), $headers);
		}

		return $surfer->request($verb, $this->apiUrl($path), null, $headers);
	}
}
