<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Site\Model\ItemModel;
use ErrorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Tests three private/security-sensitive helpers on {@see ItemModel}.
 *
 * `ItemModel` extends `BaseDatabaseModel`, whose constructor the stub does not make safe to call
 * without a real MVC factory, so every test here builds the model with
 * `ReflectionClass::newInstanceWithoutConstructor()` and reaches the private methods with Reflection
 * — none of the methods under test touch `$this->state` or the database.
 */
#[CoversClass(ItemModel::class)]
class ItemModelDownloadIdTest extends TestCase
{
	private ItemModel $model;

	private string $categoryDir;

	protected function setUp(): void
	{
		$this->model = (new ReflectionClass(ItemModel::class))->newInstanceWithoutConstructor();

		$this->categoryDir = sys_get_temp_dir() . '/ars-predownload-test-' . bin2hex(random_bytes(8));

		mkdir($this->categoryDir, 0777, true);
		file_put_contents($this->categoryDir . '/package.zip', 'x');

		mkdir($this->categoryDir . '-sibling', 0777, true);
		file_put_contents($this->categoryDir . '-sibling/secret.txt', 'x');
	}

	protected function tearDown(): void
	{
		$this->rrmdir($this->categoryDir);
		$this->rrmdir($this->categoryDir . '-sibling');
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
			is_dir($path) ? $this->rrmdir($path) : unlink($path);
		}

		rmdir($dir);
	}

	private function invokePrivate(string $method, array $args = [])
	{
		$reflectionMethod = new ReflectionMethod(ItemModel::class, $method);

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$reflectionMethod->setAccessible(true);
		}

		return $reflectionMethod->invoke($this->model, ...$args);
	}

	private function itemWithDigests(array $hashes): ItemTable
	{
		$item = (new ReflectionClass(ItemTable::class))->newInstanceWithoutConstructor();

		foreach (['sha512', 'sha256', 'sha1', 'md5'] as $algo)
		{
			$item->$algo = $hashes[$algo] ?? '';
		}

		return $item;
	}

	// ---------------------------------------------------------------------------------------------
	// reformatDownloadID()
	// ---------------------------------------------------------------------------------------------

	/**
	 * @return array<string,array{0:?string,1:string}>
	 */
	public static function provideDownloadIds(): array
	{
		$thirtyTwoAs = str_repeat('a', 32);
		$thirtyTwoBs = str_repeat('b', 32);

		return [
			'null returns empty string'                       => [null, ''],
			'empty string returns empty string'                => ['', ''],
			'a bare 32-character ID is returned unchanged'     => [$thirtyTwoAs, $thirtyTwoAs],
			'a longer ID is truncated to 32 characters'        => [str_repeat('a', 40), $thirtyTwoAs],
			'a too-short ID (< 32 chars) returns empty string' => [str_repeat('a', 31), ''],
			'the userid:dlid form keeps a positive user id'    => ['5:' . $thirtyTwoBs, '5:' . $thirtyTwoBs],
			'the userid:dlid form truncates a long dlid'       => ['5:' . str_repeat('b', 40), '5:' . $thirtyTwoBs],
			'a non-numeric user id is dropped, not preserved'  => ['notanumber:' . $thirtyTwoBs, $thirtyTwoBs],
			'a zero user id is dropped, same as no user id'    => ['0:' . $thirtyTwoBs, $thirtyTwoBs],
			'a negative user id is dropped, same as no user id' => ['-5:' . $thirtyTwoBs, $thirtyTwoBs],
			'multiple colons: only the first one splits'       => [
				'1:2:' . str_repeat('c', 32),
				'1:2:' . str_repeat('c', 30),
			],
			'surrounding whitespace is trimmed before length is checked' => [
				'  ' . $thirtyTwoAs . '  ',
				$thirtyTwoAs,
			],
			'trailing control characters count toward length and get truncated away' => [
				$thirtyTwoAs . "\x00\x01",
				$thirtyTwoAs,
			],
			'a userid: prefix with no dlid at all is too short overall' => ['5:', ''],
			'an empty user id before the colon is dropped'      => [':' . $thirtyTwoAs, $thirtyTwoAs],
			'a userid:dlid form whose dlid alone is too short is checked against the WHOLE string' => [
				'5:' . str_repeat('b', 20),
				'',
			],
		];
	}

	#[DataProvider('provideDownloadIds')]
	public function testReformatDownloadId(?string $input, string $expected): void
	{
		self::assertSame($expected, $this->model->reformatDownloadID($input));
	}

	// ---------------------------------------------------------------------------------------------
	// getContentDigestHeaderValue()
	// ---------------------------------------------------------------------------------------------

	public function testItPrefersSha512WhenEveryDigestIsPresent(): void
	{
		$item = $this->itemWithDigests([
			'sha512' => hash('sha512', 'x'),
			'sha256' => hash('sha256', 'x'),
			'sha1'   => hash('sha1', 'x'),
			'md5'    => hash('md5', 'x'),
		]);

		$expected = 'sha-512=:' . base64_encode(hex2bin(hash('sha512', 'x'))) . ':';

		self::assertSame($expected, $this->invokePrivate('getContentDigestHeaderValue', [$item]));
	}

	public function testItFallsBackToSha256WhenSha512IsMissing(): void
	{
		$item = $this->itemWithDigests([
			'sha256' => hash('sha256', 'x'),
			'sha1'   => hash('sha1', 'x'),
			'md5'    => hash('md5', 'x'),
		]);

		$expected = 'sha-256=:' . base64_encode(hex2bin(hash('sha256', 'x'))) . ':';

		self::assertSame($expected, $this->invokePrivate('getContentDigestHeaderValue', [$item]));
	}

	public function testItFallsBackToSha1WhenSha512AndSha256AreMissing(): void
	{
		$item = $this->itemWithDigests([
			'sha1' => hash('sha1', 'x'),
			'md5'  => hash('md5', 'x'),
		]);

		$expected = 'sha=:' . base64_encode(hex2bin(hash('sha1', 'x'))) . ':';

		self::assertSame($expected, $this->invokePrivate('getContentDigestHeaderValue', [$item]));
	}

	public function testItFallsBackToMd5WhenNothingElseIsPresent(): void
	{
		$item = $this->itemWithDigests(['md5' => hash('md5', 'x')]);

		$expected = 'md5=:' . base64_encode(hex2bin(hash('md5', 'x'))) . ':';

		self::assertSame($expected, $this->invokePrivate('getContentDigestHeaderValue', [$item]));
	}

	public function testItReturnsNullWhenNoDigestIsPresent(): void
	{
		$item = $this->itemWithDigests([]);

		self::assertNull($this->invokePrivate('getContentDigestHeaderValue', [$item]));
	}

	/**
	 * Regression test: a malformed stored hash (odd length, or non-hex characters) must not be passed
	 * to `hex2bin()` at all. Previously it was, which raised a PHP E_WARNING and made `hex2bin()`
	 * return `false`; `base64_encode(false)` then silently became `base64_encode('')`, so a corrupted
	 * `sha512` column produced a syntactically valid but WRONG `Content-Digest: sha-512=::` header
	 * instead of either the correct digest or a clean fallback to `sha256`/`sha1`/`md5`.
	 *
	 * This test installs its own error handler to turn any PHP warning into a catchable exception, so
	 * a regression that reintroduces the unvalidated `hex2bin()` call fails loudly instead of merely
	 * producing a wrong return value.
	 */
	#[DataProvider('provideMalformedSha512')]
	public function testAMalformedStoredHashIsSkippedWithoutWarningAndFallsBackToSha256(string $malformedSha512): void
	{
		$item = $this->itemWithDigests([
			'sha512' => $malformedSha512,
			'sha256' => hash('sha256', 'x'),
		]);

		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new ErrorException($errstr, 0, $errno);
		}, E_WARNING);

		try
		{
			$expected = 'sha-256=:' . base64_encode(hex2bin(hash('sha256', 'x'))) . ':';

			self::assertSame($expected, $this->invokePrivate('getContentDigestHeaderValue', [$item]));
		}
		finally
		{
			restore_error_handler();
		}
	}

	#[DataProvider('provideMalformedSha512')]
	public function testAMalformedStoredHashWithNoOtherDigestReturnsNullWithoutWarning(string $malformedSha512): void
	{
		$item = $this->itemWithDigests(['sha512' => $malformedSha512]);

		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new ErrorException($errstr, 0, $errno);
		}, E_WARNING);

		try
		{
			self::assertNull($this->invokePrivate('getContentDigestHeaderValue', [$item]));
		}
		finally
		{
			restore_error_handler();
		}
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public static function provideMalformedSha512(): array
	{
		return [
			'odd-length hex string'          => ['abc'],
			'even-length but non-hex string' => ['not-a-valid-hex-string!'],
		];
	}

	// ---------------------------------------------------------------------------------------------
	// getMimeTypeFromContentTypeHeader()
	// ---------------------------------------------------------------------------------------------

	/**
	 * @return array<string,array{0:?string,1:?string}>
	 */
	public static function provideContentTypeHeaders(): array
	{
		return [
			'null header returns null'                            => [null, null],
			'empty header returns null'                            => ['', null],
			'whitespace-only header returns null'                  => ['   ', null],
			'a bare MIME type is returned unchanged'               => ['application/zip', 'application/zip'],
			'parameters after a semicolon are stripped'            => ['application/zip; charset=binary', 'application/zip'],
			'a leading "Content-Type:" label is stripped'          => ['Content-Type: application/zip', 'application/zip'],
			'both a label and parameters are stripped together'    => [
				'Content-Type: application/zip; charset=binary',
				'application/zip',
			],
			'surrounding whitespace is trimmed'                    => ['  application/zip  ', 'application/zip'],
			'a trailing semicolon with nothing after it is fine'   => ['application/zip;', 'application/zip'],
		];
	}

	#[DataProvider('provideContentTypeHeaders')]
	public function testGetMimeTypeFromContentTypeHeader(?string $header, ?string $expected): void
	{
		self::assertSame($expected, $this->invokePrivate('getMimeTypeFromContentTypeHeader', [$header]));
	}

	// ---------------------------------------------------------------------------------------------
	// preDownloadCheck() — H1 regression: a crafted item `filename` escaping the category directory
	// ---------------------------------------------------------------------------------------------

	private function itemAndCategory(string $filename, string $type = 'file'): array
	{
		$item           = (new ReflectionClass(ItemTable::class))->newInstanceWithoutConstructor();
		$item->type     = $type;
		$item->filename = $filename;

		$category            = (new ReflectionClass(CategoryTable::class))->newInstanceWithoutConstructor();
		$category->directory = $this->categoryDir;

		return [$item, $category];
	}

	public function testALegitimateFileInTheCategoryDirectoryPassesTheCheck(): void
	{
		[$item, $category] = $this->itemAndCategory('package.zip');

		$this->model->preDownloadCheck($item, $category);

		$this->addToAssertionCount(1); // No exception thrown is the assertion.
	}

	public function testALinkTypeItemSkipsTheFilesystemCheckEntirely(): void
	{
		// A 'link' item has no local filename to check at all; even a nonsensical $item->filename or a
		// missing category directory must not prevent this from passing.
		[$item, $category] = $this->itemAndCategory('../whatever', 'link');

		$this->model->preDownloadCheck($item, $category);

		$this->addToAssertionCount(1);
	}

	public function testAMissingFileIsReportedAsNotFound(): void
	{
		[$item, $category] = $this->itemAndCategory('does-not-exist.zip');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);

		$this->model->preDownloadCheck($item, $category);
	}

	/**
	 * The regression case itself: a `filename` crafted to escape the category directory (e.g. set via a
	 * backend account with only category-scoped edit rights) must be reported as "not found", exactly
	 * like a missing file — NOT resolved and opened, even though the target file genuinely exists on
	 * disk one directory up.
	 */
	public function testATraversalFilenameIsReportedAsNotFoundEvenThoughTheTargetFileExists(): void
	{
		[$item, $category] = $this->itemAndCategory('../' . basename($this->categoryDir) . '-sibling/secret.txt');

		// Precondition: the traversal target genuinely exists, so a pass here would only be possible if
		// the traversal were not actually blocked.
		$this->assertFileExists($this->categoryDir . '-sibling/secret.txt');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);

		$this->model->preDownloadCheck($item, $category);
	}

	public function testABareParentTraversalFilenameIsRejected(): void
	{
		[$item, $category] = $this->itemAndCategory('..');

		$this->expectException(RuntimeException::class);

		$this->model->preDownloadCheck($item, $category);
	}
}
