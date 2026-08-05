<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Structure;

defined('_JEXEC') or die;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Structural regression test for commit 25527ca1 ("Hardening: add missing _JEXEC guard to 10
 * extension source files"), which found ten `component/`, `plugins/` and `modules/` source files that
 * could be requested directly because they were missing the `defined('_JEXEC') or die;` guard the
 * repository's CLAUDE.md mandates ("in every PHP file — no exceptions").
 *
 * This walks every `*.php` file under those three directories and asserts each one carries some form
 * of the guard. It accepts any of the four spellings actually in use across the codebase (`or`/`||`,
 * with/without parentheses on `die`) and either quote style, but does not care about capitalisation of
 * `defined`/`die` (PHP doesn't either).
 */
class JexecGuardTest extends TestCase
{
	/**
	 * Directories walked for `*.php` files, relative to the repository root.
	 */
	private const SCAN_DIRECTORIES = ['component', 'plugins', 'modules'];

	/**
	 * Files that legitimately cannot carry a `defined('_JEXEC')` guard, with the reason why.
	 *
	 * This must stay an explicit, justified, per-file list — never a blanket directory or filename
	 * pattern exclusion — so that a newly added file is guarded-by-default and this test actually
	 * catches the next missing guard, instead of quietly widening an exemption to cover it.
	 *
	 * @var array<string,string> Path relative to the repository root => why it is exempt.
	 */
	private const ALLOW_LIST = [
		'component/README.php' => 'Not a component source file: a plain-text README that happens to '
			. 'be named *.php so a misconfigured webserver never serves it as raw text. It protects '
			. 'itself with an unconditional `<?php die(); ?>` as its very first line, which is a '
			. 'stronger guard than _JEXEC (it needs no companion define() anywhere), not a weaker one.',
	];

	/**
	 * @return array<string,array{0:string}> Path relative to the repository root => same, as the sole element.
	 */
	public static function providePhpFiles(): array
	{
		$repositoryRoot = \dirname(__DIR__, 2);
		$cases          = [];

		foreach (self::SCAN_DIRECTORIES as $directory)
		{
			$absoluteDirectory = $repositoryRoot . '/' . $directory;

			if (!is_dir($absoluteDirectory))
			{
				continue;
			}

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($absoluteDirectory, FilesystemIterator::SKIP_DOTS)
			);

			/** @var SplFileInfo $file */
			foreach ($iterator as $file)
			{
				if (!$file->isFile() || strtolower($file->getExtension()) !== 'php')
				{
					continue;
				}

				$relativePath = $directory . '/' . ltrim(
					str_replace('\\', '/', substr($file->getPathname(), \strlen($absoluteDirectory))),
					'/'
				);

				$cases[$relativePath] = [$relativePath];
			}
		}

		ksort($cases);

		return $cases;
	}

	#[DataProvider('providePhpFiles')]
	public function testAPhpFileHasTheJexecGuardOrIsExplicitlyAllowListed(string $relativePath): void
	{
		if (\array_key_exists($relativePath, self::ALLOW_LIST))
		{
			self::assertNotEmpty(
				self::ALLOW_LIST[$relativePath],
				"Allow-listed file has no justification: {$relativePath}"
			);

			return;
		}

		$repositoryRoot = \dirname(__DIR__, 2);
		$contents       = file_get_contents($repositoryRoot . '/' . $relativePath);

		self::assertMatchesRegularExpression(
			'/defined\s*\(\s*[\'"]_JEXEC[\'"]\s*\)/i',
			$contents,
			"{$relativePath} is missing the defined('_JEXEC') guard."
		);
	}

	/**
	 * Every entry in the allow-list must correspond to a real file that is actually missing the guard,
	 * so a fixed or deleted file cannot leave a stale, unverified exemption behind.
	 */
	public function testEveryAllowListedFileExistsAndIsActuallyUnguarded(): void
	{
		$repositoryRoot = \dirname(__DIR__, 2);

		foreach (self::ALLOW_LIST as $relativePath => $reason)
		{
			$absolutePath = $repositoryRoot . '/' . $relativePath;

			self::assertFileExists($absolutePath, "Allow-listed file no longer exists: {$relativePath}");

			self::assertDoesNotMatchRegularExpression(
				'/defined\s*\(\s*[\'"]_JEXEC[\'"]\s*\)/i',
				file_get_contents($absolutePath),
				"{$relativePath} is allow-listed as unguarded, but actually carries a _JEXEC guard now "
				. '-- remove it from the allow-list.'
			);
		}
	}

	/**
	 * Reports the file count the task brief asks for, and doubles as a sanity check that the scan
	 * actually walked the tree: a suspiciously small count would mean {@see SCAN_DIRECTORIES} stopped
	 * resolving, which would otherwise silently turn every other test in this class into a no-op.
	 */
	public function testTheScanCoversAMeaningfulNumberOfFiles(): void
	{
		$total = \count(self::providePhpFiles());

		self::assertGreaterThan(
			100,
			$total,
			"Only {$total} PHP files were found under " . implode(', ', self::SCAN_DIRECTORIES)
			. ' -- the scan is probably broken, not the codebase shrinking that much.'
		);
	}
}
