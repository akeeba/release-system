<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\Model\BleedingedgeModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Regression coverage for the path-traversal hardening added to close the C1 finding (a crafted
 * release `version` escaping the category directory during the Bleeding Edge age/count cleanup and
 * reaching `recursiveRmdir()`) — see `security.md`.
 *
 * `resolveReleaseDirectory()` is a pure function of its two string arguments — it touches no other
 * property on the model — so the model is built with `newInstanceWithoutConstructor()` and the
 * method is exercised directly against a REAL temporary directory tree, since its whole job is to
 * call `realpath()` and compare the result.
 */
#[CoversClass(BleedingedgeModel::class)]
#[Group('Model')]
class BleedingedgeModelTest extends TestCase
{
	private string $base;

	private BleedingedgeModel $model;

	protected function setUp(): void
	{
		parent::setUp();

		$this->model = (new ReflectionClass(BleedingedgeModel::class))->newInstanceWithoutConstructor();

		$this->base = sys_get_temp_dir() . '/ars-bleedingedge-test-' . bin2hex(random_bytes(8));

		mkdir($this->base, 0777, true);
		mkdir($this->base . '/1.2.3', 0777, true);

		// A sibling directory OUTSIDE $this->base that a traversal payload might try to reach.
		mkdir($this->base . '-sibling', 0777, true);
		mkdir($this->base . '-sibling/other-release', 0777, true);
	}

	protected function tearDown(): void
	{
		$this->rrmdir($this->base);
		$this->rrmdir($this->base . '-sibling');

		parent::tearDown();
	}

	private function rrmdir(string $dir): void
	{
		if (!is_dir($dir) && !is_link($dir))
		{
			return;
		}

		if (is_link($dir))
		{
			unlink($dir);

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

	private function resolve(?string $basePath, string $version): ?string
	{
		$ref = new ReflectionMethod(BleedingedgeModel::class, 'resolveReleaseDirectory');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($this->model, $basePath, $version);
	}

	// -----------------------------------------------------------------------------------------------------------
	// Legitimate versions
	// -----------------------------------------------------------------------------------------------------------

	public function testALegitimateVersionResolvesToItsRealPath(): void
	{
		$this->assertSame(realpath($this->base . '/1.2.3'), $this->resolve($this->base, '1.2.3'));
	}

	public function testANonExistentVersionResolvesToNull(): void
	{
		$this->assertNull($this->resolve($this->base, '9.9.9'));
	}

	// -----------------------------------------------------------------------------------------------------------
	// Traversal payloads (a crafted `version` DB column value, since it carries no path-safety filter)
	// -----------------------------------------------------------------------------------------------------------

	public static function traversalPayloadProvider(): array
	{
		return [
			'parent traversal to a sibling release directory' => ['../ars-bleedingedge-test-sibling/other-release'],
			'deep parent traversal to /etc'                    => ['../../../../../../../../etc'],
			'a bare ".." segment'                               => ['..'],
			'a bare "." segment'                                 => ['.'],
			'traversal embedded after a legitimate-looking prefix' => ['1.2.3/../../../etc'],
		];
	}

	#[DataProvider('traversalPayloadProvider')]
	public function testTraversalVersionsAreRejected(string $payload): void
	{
		$this->assertNull($this->resolve($this->base, $payload));
	}

	/**
	 * Unlike an item `filename`, a release `version` never legitimately contains a path separator at
	 * all — Bleeding Edge versions are literal directory-entry names read straight off disk elsewhere
	 * in this class. So, unlike {@see \Akeeba\ARS\UnitTest\Administrator\Helper\PathSecurityTest}, ANY
	 * forward slash must be rejected outright, even one that would otherwise resolve inside the base
	 * directory.
	 */
	public function testAVersionContainingASlashIsRejectedEvenIfHarmless(): void
	{
		mkdir($this->base . '/1.2.3/nested', 0777, true);

		$this->assertNull($this->resolve($this->base, '1.2.3/nested'));
	}

	/**
	 * Belt-and-suspenders: proves the realpath()-containment check is load-bearing on its own, using a
	 * payload that passes the basename()/segment checks (no slash, no "..") but escapes the base
	 * directory via a symlink.
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
			symlink($this->base . '-sibling/other-release', $linkPath);
		}
		catch (\Throwable)
		{
			$this->markTestSkipped('This environment does not permit creating symlinks.');
		}

		$this->assertNull($this->resolve($this->base, 'escape-hatch'));
	}

	// -----------------------------------------------------------------------------------------------------------
	// Degenerate inputs
	// -----------------------------------------------------------------------------------------------------------

	public function testEmptyBasePathReturnsNull(): void
	{
		$this->assertNull($this->resolve('', '1.2.3'));
		$this->assertNull($this->resolve(null, '1.2.3'));
	}

	public function testEmptyVersionReturnsNull(): void
	{
		$this->assertNull($this->resolve($this->base, ''));
	}

	public function testNonExistentBasePathReturnsNull(): void
	{
		$this->assertNull($this->resolve($this->base . '/does-not-exist', '1.2.3'));
	}
}
