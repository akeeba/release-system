<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Helper;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\PathSecurity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the path-traversal hardening added to close the H1 finding (item
 * `filename` escaping the category directory on download, delete and hash computation) — see
 * `security.md`.
 *
 * `resolveContained()` is exercised against a REAL temporary directory tree, not a mocked
 * filesystem, since its whole job is to call `realpath()` and compare the result — a fake
 * filesystem would just be testing the fake.
 */
#[CoversClass(PathSecurity::class)]
#[Group('Helper')]
class PathSecurityTest extends TestCase
{
	private string $base;

	protected function setUp(): void
	{
		parent::setUp();

		$this->base = sys_get_temp_dir() . '/ars-pathsecurity-test-' . bin2hex(random_bytes(8));

		mkdir($this->base, 0777, true);
		mkdir($this->base . '/1.2.3', 0777, true);
		file_put_contents($this->base . '/1.2.3/package.zip', 'x');
		file_put_contents($this->base . '/toplevel.zip', 'x');

		// A sibling directory OUTSIDE $this->base that a traversal payload might try to reach.
		mkdir($this->base . '-sibling', 0777, true);
		file_put_contents($this->base . '-sibling/secret.txt', 'x');
	}

	protected function tearDown(): void
	{
		$this->rrmdir($this->base);
		$this->rrmdir($this->base . '-sibling');

		parent::tearDown();
	}

	private function rrmdir(string $dir): void
	{
		if (!is_dir($dir))
		{
			return;
		}

		foreach (scandir($dir) as $entry)
		{
			if ($entry === '.' || $entry === '..')
			{
				continue;
			}

			$path = $dir . '/' . $entry;

			if (is_link($path))
			{
				unlink($path);
			}
			elseif (is_dir($path))
			{
				$this->rrmdir($path);
			}
			else
			{
				unlink($path);
			}
		}

		rmdir($dir);
	}

	// -----------------------------------------------------------------------------------------------------------
	// Legitimate paths
	// -----------------------------------------------------------------------------------------------------------

	public function testATopLevelFileResolvesToItsRealPath(): void
	{
		$resolved = PathSecurity::resolveContained($this->base, 'toplevel.zip');

		$this->assertSame(realpath($this->base . '/toplevel.zip'), $resolved);
	}

	public function testOneLevelOfSubdirectoryResolvesCorrectly(): void
	{
		// Mirrors how BleedingedgeModel stores item filenames as "<version>/<basename>".
		$resolved = PathSecurity::resolveContained($this->base, '1.2.3/package.zip');

		$this->assertSame(realpath($this->base . '/1.2.3/package.zip'), $resolved);
	}

	public function testANonExistentFileWithinTheBaseDirectoryResolvesToNull(): void
	{
		// realpath() of a non-existent path is false, and PathSecurity treats that as "not safe to
		// use", not "safe but missing" — callers are expected to check is_file()/existence themselves
		// on a NON-null result, never to infer existence from a null one either way.
		$this->assertNull(PathSecurity::resolveContained($this->base, 'does-not-exist.zip'));
	}

	// -----------------------------------------------------------------------------------------------------------
	// Traversal payloads
	// -----------------------------------------------------------------------------------------------------------

	public static function traversalPayloadProvider(): array
	{
		return [
			'parent traversal to a sibling file'        => ['../ars-pathsecurity-test-sibling/secret.txt'],
			'parent traversal using the real sibling'   => ['..' . DIRECTORY_SEPARATOR . 'secret.txt'],
			'deep parent traversal to /etc/passwd'       => ['../../../../../../../../etc/passwd'],
			'a bare ".." segment'                        => ['..'],
			'a bare "." segment'                          => ['.'],
			'a "./" prefix'                                => ['./toplevel.zip'],
			'traversal hidden after a legitimate segment' => ['1.2.3/../../../etc/passwd'],
			'backslash-style traversal'                    => ['..\\..\\etc\\passwd'],
			'absolute POSIX path'                          => ['/etc/passwd'],
			'absolute Windows path'                        => ['C:/Windows/system.ini'],
			'absolute Windows path, backslashes'           => ['C:\\Windows\\system.ini'],
			'embedded NUL byte'                            => ["toplevel.zip\0.jpg"],
			'empty path segment (double slash)'            => ['1.2.3//package.zip'],
		];
	}

	#[DataProvider('traversalPayloadProvider')]
	public function testTraversalAndAbsolutePathsAreRejected(string $payload): void
	{
		$this->assertNull(PathSecurity::resolveContained($this->base, $payload));
	}

	/**
	 * Belt-and-suspenders: even if the segment/prefix checks were ever weakened, a payload that
	 * somehow still reached realpath() and resolved outside $this->base must be caught by the
	 * containment check, not merely by the string checks. This proves the containment check itself is
	 * load-bearing, using a payload the string checks do NOT reject (no "..", not absolute) but which
	 * a symlink makes escape the base directory anyway.
	 */
	public function testASymlinkEscapingTheBaseDirectoryIsRejected(): void
	{
		if (!function_exists('symlink'))
		{
			$this->markTestSkipped('symlink() is not available in this environment.');
		}

		$linkPath = $this->base . '/escape-hatch';

		try
		{
			symlink($this->base . '-sibling', $linkPath);
		}
		catch (\Throwable)
		{
			$this->markTestSkipped('This environment does not permit creating symlinks.');
		}

		$this->assertNull(PathSecurity::resolveContained($this->base, 'escape-hatch/secret.txt'));
	}

	// -----------------------------------------------------------------------------------------------------------
	// Degenerate inputs
	// -----------------------------------------------------------------------------------------------------------

	public function testEmptyBasePathReturnsNull(): void
	{
		$this->assertNull(PathSecurity::resolveContained('', 'toplevel.zip'));
		$this->assertNull(PathSecurity::resolveContained(null, 'toplevel.zip'));
	}

	public function testEmptyRelativePathReturnsNull(): void
	{
		$this->assertNull(PathSecurity::resolveContained($this->base, ''));
		$this->assertNull(PathSecurity::resolveContained($this->base, null));
	}

	public function testNonExistentBasePathReturnsNull(): void
	{
		$this->assertNull(PathSecurity::resolveContained($this->base . '/does-not-exist', 'toplevel.zip'));
	}
}
