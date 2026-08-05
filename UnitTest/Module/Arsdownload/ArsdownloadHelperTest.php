<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Module\Arsdownload;

defined('_JEXEC') or die;

use Joomla\Module\Arsdownload\Site\Helper\ArsdownloadHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests {@see ArsdownloadHelper::parseStreams()}, the module's normalisation of the "streams" module
 * parameter (which the module manager can hand back as an array, a JSON-encoded string, or a
 * comma-separated string, depending on which form field rendered it) into a plain array of integer
 * update-stream IDs.
 */
#[CoversClass(ArsdownloadHelper::class)]
class ArsdownloadHelperTest extends TestCase
{
	private ArsdownloadHelper $helper;

	protected function setUp(): void
	{
		$this->helper = (new ReflectionClass(ArsdownloadHelper::class))->newInstanceWithoutConstructor();
	}

	/**
	 * @param mixed $streams
	 */
	private function parseStreams($streams): array
	{
		$method = new ReflectionMethod(ArsdownloadHelper::class, 'parseStreams');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$method->setAccessible(true);
		}

		return $method->invoke($this->helper, $streams);
	}

	public function testAnArrayOfIntegersIsReturnedAsIs(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams([1, 2, 3]));
	}

	public function testAnArrayOfNumericStringsIsCastToIntegers(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams(['1', '2', '3']));
	}

	public function testAJsonEncodedArrayStringIsDecodedAndCast(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams('[1,2,3]'));
	}

	public function testACommaSeparatedStringIsSplitAndCast(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams('1,2,3'));
	}

	public function testStrayWhitespaceInACommaSeparatedStringIsTolerated(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams(' 1 , 2 ,3 '));
	}

	public function testStrayWhitespaceAroundAJsonArrayIsTolerated(): void
	{
		self::assertSame([1, 2, 3], $this->parseStreams('  [1, 2, 3]  '));
	}

	/**
	 * SUSPECTED BUG, pinned here rather than silently treated as correct. `json_decode('', true)`
	 * returns `null` (not an array), so the method falls through to `explode(',', '')`, which is
	 * `['']` — a one-element array containing an empty string. `ArrayHelper::toInteger([''])` then
	 * casts that to `[0]`. The public entry point, `ArsdownloadHelper::getItems()`, immediately does
	 * `if (empty($streams)) return [];` to detect "module not configured yet" — but `empty([0])` is
	 * `false` (a non-empty array, even though its one element is falsy), so an unconfigured module
	 * silently proceeds to call `UpdateModel::getItems(0)` for a non-existent stream ID 0 instead of
	 * short-circuiting. This is harmless in practice (there is no update stream #0), but it is a
	 * needless model call/query on every render of an unconfigured module instance.
	 */
	public function testAnEmptyStringProducesASingleZeroNotAnEmptyArray(): void
	{
		self::assertSame(
			[0],
			$this->parseStreams(''),
			'If this fails because parseStreams("") now returns [], the empty()-after-parseStreams() '
			. 'defect described above has been fixed in ArsdownloadHelper::getItems() as well -- good, '
			. 'update this test to assert [].'
		);
	}

	/**
	 * SUSPECTED BUG, pinned here rather than silently treated as correct. `explode(',', $streams)` is
	 * called on the raw `$streams` argument (not on the already-decoded `$test`), so a `null` input
	 * reaches `explode()` directly. Since PHP 8.1, passing `null` to a non-nullable `string` parameter
	 * of an internal function is deprecated, so this emits
	 * "explode(): Passing null to parameter #2 (\$string) of type string is deprecated". The public
	 * entry point never passes `null` in practice (`Registry::get('streams', '')` defaults to the
	 * empty string), so this is not reachable through normal module configuration -- but the private
	 * method's own (untyped) signature and its behaviour when called directly are worth pinning. The
	 * deprecation is suppressed here with `@` deliberately, so this one known, already-reported gap
	 * does not add a spurious `Deprecations:` line to the whole suite's result.
	 */
	public function testNullIsTreatedTheSameAsAnEmptyStringDespiteADeprecationNotice(): void
	{
		$method = new ReflectionMethod(ArsdownloadHelper::class, 'parseStreams');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$method->setAccessible(true);
		}

		self::assertSame([0], @$method->invoke($this->helper, null));
	}
}
