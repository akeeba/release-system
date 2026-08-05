<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Structure;

defined('_JEXEC') or die;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Structural regression test for the repository CLAUDE.md's stated gotcha: "Every schema change needs
 * both dialects. `component/backend/sql/` ships parallel MySQL and PostgreSQL files; changing one and
 * not the other ships a broken install."
 *
 * PostgreSQL support only began at version 7.4.2 (commit 4b00cf2a, "+ PostgreSQL support
 * (experimental)"), which added `component/backend/sql/updates/postgresql/7.4.2-20250804-0000.sql`
 * alongside the pre-existing MySQL file of the same name. Every MySQL update file older than that is
 * legitimately MySQL-only. That floor is expressed once, below, as a version string parsed out of each
 * filename with `version_compare()` — not as a hard-coded list of the seven pre-7.4.2 filenames, which
 * would silently stop guarding the moment someone adds an eighth pre-floor file (impossible for new
 * work, but exactly the kind of exclusion that quietly rots).
 */
class SqlDialectParityTest extends TestCase
{
	/**
	 * The first ARS version for which a PostgreSQL update file is expected to exist. See commit
	 * 4b00cf2a.
	 */
	private const PGSQL_FLOOR = '7.4.2';

	private const SQL_ROOT = 'component/backend/sql';

	private static function sqlRoot(): string
	{
		return \dirname(__DIR__, 2) . '/' . self::SQL_ROOT;
	}

	/**
	 * Extracts the leading `major.minor.patch` version from an update filename such as
	 * `7.4.2-20250804-0000.sql` or `7.0.0-20210430.sql`.
	 */
	private static function versionFromFilename(string $filename): ?string
	{
		if (preg_match('/^(\d+\.\d+\.\d+)-/', $filename, $matches))
		{
			return $matches[1];
		}

		return null;
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public static function provideMysqlUpdateFiles(): array
	{
		$directory = self::sqlRoot() . '/updates/mysql';
		$cases     = [];

		foreach (scandir($directory) ?: [] as $filename)
		{
			if ($filename === '.' || $filename === '..' || !str_ends_with($filename, '.sql'))
			{
				continue;
			}

			$cases[$filename] = [$filename];
		}

		ksort($cases);

		return $cases;
	}

	/**
	 * Every MySQL update file at or above the PostgreSQL floor must have a same-named PostgreSQL
	 * counterpart. Files below the floor predate PostgreSQL support and are expected to be MySQL-only.
	 */
	#[DataProvider('provideMysqlUpdateFiles')]
	public function testAMysqlUpdateFileAtOrAboveTheFloorHasAPostgresqlCounterpart(string $filename): void
	{
		$version = self::versionFromFilename($filename);

		self::assertNotNull($version, "Cannot parse a version out of update filename: {$filename}");

		$postgresqlPath = self::sqlRoot() . '/updates/postgresql/' . $filename;

		if (version_compare($version, self::PGSQL_FLOOR, '<'))
		{
			self::assertFileDoesNotExist(
				$postgresqlPath,
				"{$filename} is below the PostgreSQL floor (" . self::PGSQL_FLOOR . ') yet a PostgreSQL '
				. 'file exists for it -- either the floor constant is now wrong, or this file was '
				. 'backported and the floor logic needs revisiting.'
			);

			return;
		}

		self::assertFileExists(
			$postgresqlPath,
			"{$filename} is at or above the PostgreSQL floor (" . self::PGSQL_FLOOR . ') but has no '
			. 'matching component/backend/sql/updates/postgresql/' . $filename . ' -- this schema '
			. 'change was applied to MySQL only.'
		);
	}

	/**
	 * The reverse direction: every PostgreSQL update file must have a same-named MySQL counterpart.
	 * PostgreSQL never had a period of exclusivity, so there is no floor to apply here.
	 */
	public function testEveryPostgresqlUpdateFileHasAMysqlCounterpart(): void
	{
		$directory = self::sqlRoot() . '/updates/postgresql';
		$missing   = [];

		foreach (scandir($directory) ?: [] as $filename)
		{
			if ($filename === '.' || $filename === '..' || !str_ends_with($filename, '.sql'))
			{
				continue;
			}

			if (!is_file(self::sqlRoot() . '/updates/mysql/' . $filename))
			{
				$missing[] = $filename;
			}
		}

		self::assertSame(
			[],
			$missing,
			"These PostgreSQL update files have no MySQL counterpart:\n" . implode("\n", $missing)
		);
	}

	/**
	 * The base install/uninstall scripts for both dialects must all exist -- there is no floor version
	 * for these, they are the starting point.
	 */
	public function testTheBaseInstallAndUninstallScriptsExistForBothDialects(): void
	{
		foreach (
			[
				'install.mysql.utf8.sql',
				'install.postgresql.utf8.sql',
				'uninstall.mysql.utf8.sql',
				'uninstall.postgresql.utf8.sql',
			] as $filename
		)
		{
			self::assertFileExists(self::sqlRoot() . '/' . $filename);
		}
	}

	/**
	 * Reports the counts the task brief asks for, and doubles as a sanity check that the two update
	 * directories were actually found: a count of zero on either side would otherwise make every other
	 * test in this class vacuously pass.
	 */
	public function testTheUpdateDirectoriesContainAMeaningfulNumberOfFiles(): void
	{
		$mysqlCount      = \count(self::provideMysqlUpdateFiles());
		$postgresqlCount = \count(array_filter(
			scandir(self::sqlRoot() . '/updates/postgresql') ?: [],
			static fn(string $f) => str_ends_with($f, '.sql')
		));

		self::assertGreaterThan(0, $mysqlCount, 'No MySQL update files were found -- the scan is probably broken.');
		self::assertGreaterThan(
			0,
			$postgresqlCount,
			'No PostgreSQL update files were found -- the scan is probably broken.'
		);
	}
}
