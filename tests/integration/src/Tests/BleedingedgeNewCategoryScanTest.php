<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression for GitHub issue #254: a brand-new Bleeding Edge category never picked up the release
 * folder that was uploaded before it was created.
 *
 * The normal workflow (and the reporter's, and this project's own, for the past 16 years) is to
 * upload the version folder to the repository first, then create the ARS category that points at it
 * afterwards. `BleedingedgeModel::scanCategory()` skips its (expensive) scan when the directory's
 * mtime is not newer than the category's `modified` (falling back to `created`) column, on the theory
 * that nothing changed on disk since the category was last looked at. Before the fix,
 * `TableCreateModifyTrait::onBeforeStore()` stamped a brand-new row's `modified` to the same instant
 * as `created`, so a category created any time after its directory was uploaded looked "already
 * scanned" the moment it was saved — permanently, since the only place that timestamp could advance
 * was inside the very block the early return skips. The fix is two-part: a genuinely new row now
 * gets `modified = NULL` (a row that has never been edited has nothing to report as "modified"), and
 * `scanCategory()` no longer falls back to `created` — a NULL `modified` means "never scanned", so the
 * first scan always runs regardless of how the directory's mtime compares to the category's age.
 *
 * @since 7.6.0
 */
#[Group('regression')]
class BleedingedgeNewCategoryScanTest extends AbstractE2ETestCase
{
	private const MARKER = 'E2E-ISSUE-254';

	private string $directory = '';

	protected function tearDown(): void
	{
		$categoryIds = $this->db()->column(
			'SELECT id FROM `#__ars_categories` WHERE title LIKE ?',
			[self::MARKER . '%']
		);

		foreach ($categoryIds as $categoryId)
		{
			$this->db()->query('DELETE FROM `#__ars_items` WHERE release_id IN (SELECT id FROM `#__ars_releases` WHERE category_id = ?)', [$categoryId]);
			$this->db()->query('DELETE FROM `#__ars_releases` WHERE category_id = ?', [$categoryId]);
			$this->db()->query('DELETE FROM `#__assets` WHERE name = ?', ['com_ars.category.' . $categoryId]);
			$this->db()->query('DELETE FROM `#__ars_categories` WHERE id = ?', [$categoryId]);
		}

		if ($this->directory !== '')
		{
			$this->cli()->run(['rm', '-rf', $this->directory]);
		}

		parent::tearDown();
	}

	/**
	 * Reproduces the reporter's exact workflow: the release folder exists on disk (with an old mtime)
	 * before the category pointing at it is ever created. Before the fix, this permanently starved the
	 * category of its first scan; after the fix, the very first request against the category's
	 * releases view discovers the folder and creates the release for it.
	 *
	 * @return  void
	 * @since   7.6.0
	 */
	public function testANewCategoryDiscoversAFolderUploadedBeforeItWasCreated(): void
	{
		$suffix          = uniqid();
		$this->directory = 'arsrepo/e2e-issue254-' . $suffix;
		$version         = '1.0.0';

		// 1. Upload the release folder first — the folder's mtime is set well in the past, so it is
		//    guaranteed to be older than the category we are about to create, exactly like a folder
		//    that was uploaded some time before anyone went to the backend to create the category.
		[$exitCode, $output] = $this->cli()->run(['mkdir', '-p', $this->directory . '/' . $version]);
		$this->assertSame(0, $exitCode, "Could not create the fixture directory.\n" . $output);

		[$exitCode, $output] = $this->cli()->run([
			'sh', '-c',
			sprintf(
				'echo %s > %s && touch -d "1 hour ago" %s %s',
				escapeshellarg(self::MARKER . ' payload'),
				escapeshellarg($this->directory . '/' . $version . '/payload.bin'),
				escapeshellarg($this->directory . '/' . $version),
				escapeshellarg($this->directory . '/' . $version . '/payload.bin')
			),
		]);
		$this->assertSame(0, $exitCode, "Could not seed the fixture payload file.\n" . $output);

		// 2. NOW create the Bleeding Edge category, pointing at that already-uploaded folder. Its
		//    `created`/`modified` timestamps will be "now" — an hour AFTER the directory's mtime.
		$admin = $this->superUser();
		$token = $admin->fetchToken($this->adminUrl(['view' => 'categories']));

		$title = self::MARKER . ' ' . $suffix;
		$alias = 'e2e-issue-254-' . $suffix;

		$admin->followRedirects = false;
		$response               = $admin->post($this->adminUrl([]), [
			'jform' => [
				'id'                => '0',
				'title'             => $title,
				'alias'             => $alias,
				'type'              => 'bleedingedge',
				'directory'         => $this->directory,
				'access'            => '1',
				'show_unauth_links' => '0',
				'is_supported'      => '1',
				'language'          => '*',
				'published'         => '1',
			],
			'task'  => 'category.save',
			$token  => 1,
		]);

		$this->assertTrue(
			$response->isRedirect(),
			"Creating the Bleeding Edge category did not redirect (did the save fail?).\n" . $response->summary()
		);

		$categoryId = (int) $this->db()->value('SELECT id FROM `#__ars_categories` WHERE alias = ?', [$alias]);
		$this->assertGreaterThan(0, $categoryId, 'The Bleeding Edge category was not created.');

		$modifiedAfterCreate = $this->db()->value('SELECT modified FROM `#__ars_categories` WHERE id = ?', [$categoryId]);
		$this->assertTrue(
			$modifiedAfterCreate === null || $modifiedAfterCreate === '0000-00-00 00:00:00',
			'A brand-new category row was stamped with a non-NULL `modified` value, which is exactly what '
			. 'made scanCategory() think the category had already been scanned at creation time: '
			. var_export($modifiedAfterCreate, true)
		);

		// 3. Visit the category's releases view, exactly as a customer browsing the site would. This is
		//    what triggers ReleasesController::onBeforeDisplay() -> BleedingedgeModel::scanCategory().
		$response = $this->guest()->get($this->siteUrl(['view' => 'releases', 'category_id' => $categoryId]));

		$this->assertStatus(200, $response, 'The releases view did not render for the new Bleeding Edge category.');

		// 4. The release must now exist, proving the scan actually ran despite the directory predating
		//    the category.
		$release = $this->db()->row(
			'SELECT id, published FROM `#__ars_releases` WHERE category_id = ? AND version = ?',
			[$categoryId, $version]
		);

		$this->assertNotNull(
			$release,
			'The pre-existing release folder was never discovered: a brand-new category permanently '
			. 'skipped its first Bleeding Edge scan because the folder\'s mtime was older than the '
			. 'category\'s own creation time.'
		);
		$this->assertSame(1, (int) $release['published'], 'The discovered Bleeding Edge release was not published.');
	}
}
