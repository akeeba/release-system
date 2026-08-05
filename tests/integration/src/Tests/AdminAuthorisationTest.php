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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Back-end authorisation: the save2copy regression and the anti-CSRF gate on every batch/reset task.
 *
 * @since 7.5.0
 */
#[Group('authorisation')]
class AdminAuthorisationTest extends AbstractE2ETestCase
{
	private const MARKER = 'E2E-WRITE-TEST';

	protected function tearDown(): void
	{
		$this->db()->query('DELETE FROM `#__ars_releases` WHERE version LIKE ?', [self::MARKER . '%']);
		$this->db()->query('DELETE FROM `#__ars_items` WHERE title LIKE ?', [self::MARKER . '%']);
		$this->db()->query('DELETE FROM `#__ars_dlidlabels` WHERE title LIKE ?', [self::MARKER . '%']);

		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Regression for commit 08e4a68b, "Replace the Copy task with Save as Copy".
	//
	// The old list-view Copy task relied on Joomla's AdminModel::batchCopy(), whose only privilege
	// check lives in checkCategoryId() — a check ARS's ModelCopyTrait overrides away because no ARS
	// record lives in a Joomla category. It never consulted core.create. Save as Copy routes the
	// request through FormController::save()'s ordinary allowAdd() check instead, so the fix is that
	// the Create privilege is now enforced by the framework rather than by an absent toolbar button.
	// -----------------------------------------------------------------------

	/**
	 * catManager holds core.edit but not core.create on the restricted category (asserted by
	 * HarnessTest::testAclMatrixIsWhatItClaimsToBe()). A release.save2copy submission for a release in
	 * that category must be refused, and no duplicate row created.
	 *
	 * @since 7.5.0
	 */
	public function testSave2CopyIsRefusedWithoutCreateInTheCategory(): void
	{
		$catManager = $this->loggedInBackend('catManager');
		$releaseId  = static::$fixtures->releaseId('restrictedStable');
		$token      = $this->fetchReleaseEditToken($catManager, $releaseId);

		$version = self::MARKER . ' restricted copy ' . uniqid();

		$catManager->followRedirects = false;
		$response = $catManager->post($this->adminUrl([]), [
			'jform' => [
				'id'          => (string) $releaseId,
				'category_id' => (string) static::$fixtures->categoryId('restricted'),
				'version'     => $version,
				'alias'       => 'e2e-write-test-restricted-copy-' . uniqid(),
				'maturity'    => 'stable',
			],
			'task'  => 'release.save2copy',
			$token  => 1,
		]);

		$this->assertRefused(
			$catManager,
			$response,
			'catManager was able to Save as Copy a release in the restricted category, where it holds core.edit '
			. 'but not core.create.'
		);
		$this->assertSame(
			0,
			(int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE version = ?', [$version]),
			'A duplicate release row was created despite the refusal.'
		);
	}

	/**
	 * The same actor, the same task, but the public category — where catManager DOES hold core.create
	 * (per the same ACL matrix). This is the control: it proves the refusal above is the per-category
	 * create check actually working, not the save2copy task being broken for everyone.
	 *
	 * `ReleaseModel::save()` (commit `639703cd`) now mirrors `CategoryModel`: on `save2copy` it derives
	 * a version/alias pair unique in the target category before delegating to `parent::save()`,
	 * incrementing the version with `StringHelper::increment()` and re-running
	 * `ModelCopyTrait::generateNewTitle()` in a bounded loop until neither collides, so
	 * `ReleaseTable::onBeforeCheck()` never sees the pair it used to reject. That auto-increment is
	 * exactly what this test's inputs are engineered to avoid: `version` and `alias` are both suffixed
	 * with `uniqid()`, so they never collide with the original release and the auto-increment path is
	 * never exercised. This test therefore still exercises only the per-category `core.create` check —
	 * the control for the refusal above — and stays silent on the auto-increment behaviour, which
	 * belongs to a test of its own.
	 *
	 * @since 7.5.0
	 */
	public function testSave2CopySucceedsWithCreateInTheCategory(): void
	{
		$catManager = $this->loggedInBackend('catManager');
		$releaseId  = static::$fixtures->releaseId('publicStable');
		$token      = $this->fetchReleaseEditToken($catManager, $releaseId);

		$version = self::MARKER . ' public copy ' . uniqid();
		$alias   = 'e2e-write-test-public-copy-' . uniqid();

		$catManager->followRedirects = false;
		$response = $catManager->post($this->adminUrl([]), [
			'jform' => [
				'id'          => (string) $releaseId,
				'category_id' => (string) static::$fixtures->categoryId('public'),
				'version'     => $version,
				'alias'       => $alias,
				'maturity'    => 'stable',
			],
			'task'  => 'release.save2copy',
			$token  => 1,
		]);

		$this->assertTrue(
			$response->isRedirect(),
			"catManager's Save as Copy in the public category, where it holds core.create, did not redirect.\n"
			. $response->summary()
		);

		$row = $this->db()->row(
			'SELECT id, category_id, alias FROM `#__ars_releases` WHERE version = ?',
			[$version]
		);

		$this->assertIsArray($row, 'The copy was not created even though catManager holds core.create there.');
		$this->assertNotSame(
			$releaseId,
			(int) $row['id'],
			'The "copy" is the same row as the original — save2copy did not create a new record.'
		);
		$this->assertSame(
			$alias,
			$row['alias'],
			'The copy was stored with a different alias than the one submitted.'
		);
	}

	// -----------------------------------------------------------------------
	// Anti-CSRF on every batch()/reset() task.
	//
	// BaseController::checkToken() does not throw on a bad token: it enqueues JINVALID_TOKEN_NOTICE
	// and redirects to the referrer. assertRefused() knows this shape, but it must be given the SAME
	// surfer that made the request, because the message lives in that session.
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 * @since  7.5.0
	 */
	public static function batchTaskProvider(): array
	{
		return [
			'CategoryController::batch()' => ['category', 'categories', 'access'],
			'ReleaseController::batch()'  => ['release', 'releases', 'access'],
			'ItemController::batch()'     => ['item', 'items', 'access'],
		];
	}

	/**
	 * A corrupted anti-CSRF token refuses the batch task, and the intended change (an access level
	 * change, the same batch operation every one of these controllers' list views expose) does not
	 * happen.
	 *
	 * @since 7.5.0
	 */
	#[DataProvider('batchTaskProvider')]
	public function testBatchRefusesACorruptedToken(string $controller, string $listView, string $column): void
	{
		$admin = $this->superUser();

		[$table, $recordId, $originalValue] = $this->throwawayRecordFor($controller);

		$listResp = $admin->get($this->adminUrl(['view' => $listView]));
		$token    = $admin->getFormToken($listResp->body);
		$this->assertNotNull($token, sprintf('No anti-CSRF token found on the %s list view.', $listView));

		$badToken = $admin->corruptToken($token);
		$newValue = (int) static::$fixtures->viewLevelId('secret');

		$admin->followRedirects = false;
		$response = $admin->post($this->adminUrl([]), [
			'task'       => $controller . '.batch',
			'cid'        => [(string) $recordId],
			'batch'      => ['assetgroup_id' => (string) $newValue],
			'boxchecked' => '1',
			$badToken    => 1,
		]);

		$this->assertRefused(
			$admin,
			$response,
			sprintf('%s accepted a batch request with a corrupted anti-CSRF token.', $controller . '.batch()')
		);

		$actual = (int) $this->db()->value(sprintf('SELECT %s FROM `%s` WHERE id = ?', $column, $table), [$recordId]);

		$this->assertSame(
			$originalValue,
			$actual,
			sprintf('%s changed the record despite the corrupted token.', $controller . '.batch()')
		);

		// Prove the batch task actually performs the change for a real token, so the refusal above is
		// not simply this endpoint being unreachable.
		$response2 = $admin->post($this->adminUrl([]), [
			'task'       => $controller . '.batch',
			'cid'        => [(string) $recordId],
			'batch'      => ['assetgroup_id' => (string) $newValue],
			'boxchecked' => '1',
			$token       => 1,
		]);

		$this->assertTrue(
			$response2->isRedirect(),
			sprintf('%s with a valid token did not complete.', $controller . '.batch()') . "\n" . $response2->summary()
		);

		$actual2 = (int) $this->db()->value(sprintf('SELECT %s FROM `%s` WHERE id = ?', $column, $table), [$recordId]);

		$this->assertSame(
			$newValue,
			$actual2,
			sprintf(
				'%s with a valid token did not change the record, so the refusal above does not prove the token check works.',
				$controller . '.batch()'
			)
		);

		$this->cleanupThrowawayRecord($controller, $table, $recordId, $originalValue);
	}

	/**
	 * DlidlabelsController::reset() is not a generic batch task, but it is guarded the same way:
	 * checkToken() first, and a corrupted token must not regenerate the Download ID.
	 *
	 * @since 7.5.0
	 */
	public function testDlidlabelsResetRefusesACorruptedToken(): void
	{
		$admin  = $this->superUser();
		$dlidId = $this->db()->insert('#__ars_dlidlabels', [
			'user_id'   => static::$fixtures->userId('subscriber'),
			'title'     => self::MARKER . ' reset target',
			'dlid'      => str_repeat('a', 32),
			'primary'   => 0,
			'published' => 1,
			'created'   => date('Y-m-d H:i:s'),
		]);

		$before = $this->db()->value('SELECT dlid FROM `#__ars_dlidlabels` WHERE id = ?', [$dlidId]);

		$listResp = $admin->get($this->adminUrl(['view' => 'dlidlabels']));
		$token    = $admin->getFormToken($listResp->body);
		$this->assertNotNull($token, 'No anti-CSRF token found on the Download ID labels list view.');

		$badToken = $admin->corruptToken($token);

		$admin->followRedirects = false;
		$response = $admin->post($this->adminUrl([]), [
			'task'       => 'dlidlabels.reset',
			'cid'        => [(string) $dlidId],
			'boxchecked' => '1',
			$badToken    => 1,
		]);

		$this->assertRefused(
			$admin,
			$response,
			'DlidlabelsController::reset() accepted a request with a corrupted anti-CSRF token.'
		);

		$after = $this->db()->value('SELECT dlid FROM `#__ars_dlidlabels` WHERE id = ?', [$dlidId]);

		$this->assertSame($before, $after, 'The Download ID was regenerated despite the corrupted token.');

		// Prove reset() actually regenerates the Download ID for a valid token.
		$response2 = $admin->post($this->adminUrl([]), [
			'task'       => 'dlidlabels.reset',
			'cid'        => [(string) $dlidId],
			'boxchecked' => '1',
			$token       => 1,
		]);

		$this->assertTrue(
			$response2->isRedirect(),
			"dlidlabels.reset with a valid token did not complete.\n" . $response2->summary()
		);

		$after2 = $this->db()->value('SELECT dlid FROM `#__ars_dlidlabels` WHERE id = ?', [$dlidId]);

		$this->assertNotSame(
			$before,
			$after2,
			'dlidlabels.reset with a valid token did not change the Download ID, so the refusal above does not '
			. 'prove the token check works.'
		);

		$this->db()->query('DELETE FROM `#__ars_dlidlabels` WHERE id = ?', [$dlidId]);
	}

	// -----------------------------------------------------------------------
	// A guest cannot reach any ARS admin view.
	// -----------------------------------------------------------------------

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 * @since  7.5.0
	 */
	public static function adminViewProvider(): array
	{
		return [
			'categories list' => [['view' => 'categories']],
			'releases list'   => [['view' => 'releases']],
			'items list'      => [['view' => 'items']],
			'control panel'   => [['view' => 'controlpanel']],
		];
	}

	#[DataProvider('adminViewProvider')]
	public function testGuestCannotReachAnAdminView(array $query): void
	{
		$guest = $this->guest();
		$guest->followRedirects = true;

		$response = $guest->get($this->adminUrl($query));

		$this->assertTrue(
			$this->guestWasSentToLogin($response),
			sprintf(
				'A guest reached %s without being sent to the back-end login form.',
				$this->adminUrl($query)
			) . "\n" . $response->summary()
		);
	}

	/**
	 * A guest hitting a back-end URL ends up looking at the login form: either Joomla's back-end
	 * itself intercepted the request before com_ars ever ran, or com_ars refused and the redirect
	 * chain still lands there. Either way the identifying marker is the login form's password field.
	 *
	 * @since 7.5.0
	 */
	private function guestWasSentToLogin(Response $response): bool
	{
		return stripos($response->body, 'name="passwd"') !== false
			|| in_array($response->code, [401, 403], true);
	}

	// -----------------------------------------------------------------------
	// Helpers.
	// -----------------------------------------------------------------------

	/**
	 * GET a release's edit form and pull the anti-CSRF token out of it, following the checkout
	 * redirect FormController::edit() issues.
	 *
	 * @since 7.5.0
	 */
	private function fetchReleaseEditToken(Surfer $surfer, int $releaseId): string
	{
		$surfer->followRedirects = false;

		$response = $surfer->get($this->adminUrl(['view' => 'release', 'task' => 'release.edit', 'id' => $releaseId]));

		if ($response->isRedirect())
		{
			$response = $surfer->get($response->getLocation());
		}

		$token = $surfer->getFormToken($response->body);

		$this->assertNotNull($token, 'No anti-CSRF token found on the release edit form.');

		return $token;
	}

	/**
	 * Create a throwaway record for the given controller's batch task and return
	 * [table name (with #__ prefix), record id, its starting `access` value].
	 *
	 * @return array{0: string, 1: int, 2: int}
	 * @since  7.5.0
	 */
	private function throwawayRecordFor(string $controller): array
	{
		switch ($controller)
		{
			case 'category':
				// Categories carry real directories and asset rules the provisioner builds; batching a
				// fixture category's access and restoring it afterwards is simpler and safer than trying
				// to build a whole new one by hand. bleedingedge is not depended on by any other test.
				$id = static::$fixtures->categoryId('bleedingedge');

				return ['#__ars_categories', $id, (int) $this->db()->value('SELECT access FROM `#__ars_categories` WHERE id = ?', [$id])];

			case 'release':
				$id = $this->db()->insert('#__ars_releases', [
					'category_id' => static::$fixtures->categoryId('public'),
					'version'     => self::MARKER . ' batch ' . uniqid(),
					'alias'       => 'e2e-write-test-batch-' . uniqid(),
					'maturity'    => 'stable',
					'access'      => 1,
					'ordering'    => 0,
					'published'   => 1,
					'language'    => '*',
				]);

				return ['#__ars_releases', $id, 1];

			case 'item':
				$id = $this->db()->insert('#__ars_items', [
					'release_id'  => static::$fixtures->releaseId('publicStable'),
					'title'       => self::MARKER . ' batch item ' . uniqid(),
					'alias'       => 'e2e-write-test-batch-item-' . uniqid(),
					'description' => '',
					'type'        => 'link',
					'url'         => 'https://example.test/e2e-write-test',
					'access'      => 1,
					'ordering'    => 0,
					'published'   => 1,
					'language'    => '*',
				]);

				return ['#__ars_items', $id, 1];
		}

		throw new \InvalidArgumentException('Unknown controller: ' . $controller);
	}

	/**
	 * Undo throwawayRecordFor(): restore the fixture category's original access, or delete the
	 * throwaway row.
	 *
	 * @since 7.5.0
	 */
	private function cleanupThrowawayRecord(string $controller, string $table, int $recordId, int $originalAccess): void
	{
		if ($controller === 'category')
		{
			$this->db()->query('UPDATE `#__ars_categories` SET access = ? WHERE id = ?', [$originalAccess, $recordId]);

			return;
		}

		$this->db()->query(sprintf('DELETE FROM `%s` WHERE id = ?', $table), [$recordId]);
	}
}
