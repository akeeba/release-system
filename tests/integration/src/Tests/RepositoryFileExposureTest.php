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
 * ARS serves release files from a directory under the site root (`arsrepo/`, per
 * `SiteProvisioner::repositoryPath()`). Every access check ARS has — item, release and category level
 * view-level authorisation, Download IDs — lives in `ItemController`/`ItemModel`, the PHP download
 * controller reached through `index.php`. None of it applies to a plain HTTP request for the file's
 * own path under the web root. If the web server serves that path directly, the entire authorisation
 * layer this suite spends six other classes testing is bypassable by guessing (or simply reading off
 * a rendered page) a filename.
 *
 * ⚠ DOCUMENTED EXPOSURE, confirmed live: it is. See {@see testRestrictedFileIsDirectlyFetchableFromTheRepositoryPath()}.
 *
 * @since 7.5.0
 */
#[Group('exposure')]
class RepositoryFileExposureTest extends AbstractE2ETestCase
{
	/**
	 * ⚠ DOCUMENTED EXPOSURE, confirmed live, NOT asserted as acceptable: `restrictedFile` — gated
	 * behind the ARS Subscribers view level, refused to a guest through every front-end route this
	 * suite exercises (`DownloadAuthorisationTest`, `CategoryBrowsingTest`) — is fully readable by an
	 * unauthenticated guest fetching its plain path directly under the site root, with none of ARS's
	 * PHP-level access control in the way at all. The Apache document root serves
	 * `arsrepo/<relative path>` like any other static file: HTTP 200, `Content-Length` matching the
	 * file on disk, and the file's own sentinel string in the body.
	 *
	 * This bypasses EVERY authorisation check in this entire test suite. Anybody who can determine a
	 * release file's relative path — from a rendered download link, from an update-stream
	 * `<downloadurl>`, or simply by guessing a filename pattern ARS itself generates predictably — can
	 * fetch it with a single unauthenticated GET, no `dlid`, no session, no category or release or item
	 * access check of any kind.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRestrictedFileIsDirectlyFetchableFromTheRepositoryPath(): void
	{
		$file = static::$fixtures->file('restrictedFile');
		$url  = static::$config->getSiteUrl() . '/' . ltrim($file['relative'], '/');

		$surfer   = new Surfer(static::$config->getSiteUrl());
		$response = $surfer->get($url);

		// This assertion intentionally captures the exposure, not a hoped-for fix: it is written to
		// FAIL the moment the repository is protected, so that this comment and the class docblock
		// above stop being true and must be updated (change to assertRefused() + assertBodyNotContains()
		// once the exposure is closed).
		$this->assertStatus(
			200,
			$response,
			'The restricted release file was NOT directly fetchable — if this assertion now fails because the '
			. 'repository path is protected, that is good news: update this test (and the class docblock, which '
			. 'documents the exposure) to assert the file is refused instead.'
		);
		$this->assertBodyContains(
			$file['sentinel'],
			$response,
			'The repository path served a 200 for the restricted file but somehow not its actual contents.'
		);
		$this->assertSame(
			(string) $file['size'],
			$response->getHeader('content-length'),
			'The repository path served the restricted file but with an unexpected Content-Length.'
		);
	}

	/**
	 * The same exposure applies to the secret-category file — the one category no test account, not
	 * even the manager, can reach through any front-end route.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testSecretFileIsDirectlyFetchableFromTheRepositoryPath(): void
	{
		$file = static::$fixtures->file('secretFile');
		$url  = static::$config->getSiteUrl() . '/' . ltrim($file['relative'], '/');

		$surfer   = new Surfer(static::$config->getSiteUrl());
		$response = $surfer->get($url);

		$this->assertStatus(
			200,
			$response,
			'The secret release file was NOT directly fetchable — if this assertion now fails because the '
			. 'repository path is protected, update this test to assert the file is refused instead.'
		);
		$this->assertBodyContains(
			$file['sentinel'],
			$response,
			'The repository path served a 200 for the secret file but somehow not its actual contents.'
		);
	}

	/**
	 * ARS ships `.htaccess` (and `web.config`) into every plugin and module folder it installs
	 * (`plugins/content/arsdlid/.htaccess`, `modules/site/arsdownloads/.htaccess`, etc. — deny-all
	 * files that stop the web server from executing or listing those directories directly). It does
	 * NOT ship anything equivalent into the release repository directory: confirmed by reading
	 * `CategoryTable::onBeforeCheck()`, which only ever calls `is_dir()` on the category's `directory`
	 * and never writes a protective file into it, and confirmed live by fetching the file directly
	 * above. This test asserts that absence explicitly, as the root cause of the exposure.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testRepositoryDirectoryHasNoAccessRestrictionFile(): void
	{
		$surfer = new Surfer(static::$config->getSiteUrl());
		$base   = static::$config->getSiteUrl() . '/' . static::$fixtures->repositoryPath() . '/';

		foreach (['.htaccess', 'web.config'] as $protectiveFile)
		{
			$response = $surfer->get($base . $protectiveFile);

			// Apache returns 403 for a dotfile it is configured to deny regardless of whether the file
			// exists (AllowOverride/Files directives match on the name, not on presence), so a bare
			// status check cannot tell "protected and present" apart from "blocked by name only, absent
			// either way". Check on disk directly instead, which is unambiguous.
			$onDiskPath = rtrim(static::$config->getSiteRoot(), '/') . '/' . static::$fixtures->repositoryPath() . '/' . $protectiveFile;

			$this->assertFileDoesNotExist(
				$onDiskPath,
				sprintf(
					'A %s file exists in the repository directory (%s) — the exposure documented in this class may '
					. 'already be mitigated; re-check testRestrictedFileIsDirectlyFetchableFromTheRepositoryPath().',
					$protectiveFile,
					$onDiskPath
				)
			);
		}
	}
}
