<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Update\Common;
use Joomla\CMS\MVC\View\HtmlView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A test host for the {@see Common} trait. See {@see \Akeeba\ARS\UnitTest\Site\View\Update\PlatformCompactorHost}
 * (declared alongside `PlatformVersionCompactorTest`) for why a host class is needed at all; this one
 * additionally exposes `$envs`, which `getParsedPlatforms()` reads to resolve each environment ID in
 * `$item->environments` to a `platform/version` string.
 */
class ParsedPlatformsHost extends HtmlView
{
	use Common;

	/** @var array<int,string> Environment ID => "platform/version", e.g. `1 => 'joomla/4.2'`. */
	public array $envs = [];

	public $category;
}

/**
 * Tests {@see Common::getParsedPlatforms()}.
 *
 * Every test here feeds an item with a non-empty `environments` array *and* a populated `$this->envs`,
 * so the method resolves real platform strings and never falls through to its
 * `Version::MAJOR_VERSION` / `PHP_MAJOR_VERSION` fallback (which would tie the test to whatever
 * environment PHPUnit happens to run under).
 */
#[CoversClass(Common::class)]
class ParsedPlatformsTest extends TestCase
{
	private ParsedPlatformsHost $host;

	protected function setUp(): void
	{
		$this->host        = new ParsedPlatformsHost();
		$this->host->envs  = [
			1 => 'joomla/4.2',
			2 => 'joomla/4.3',
			3 => 'php/8.1',
		];
	}

	private function item(array $environments): object
	{
		return (object) ['environments' => $environments];
	}

	public function testTheOrdinaryParseGroupsJoomlaAndPhpSeparatelyAndCompacts(): void
	{
		$result = $this->host->getParsedPlatforms($this->item([1, 2, 3]), true, false);

		self::assertSame(
			[
				'platforms' => [['joomla', '4\\.(2|3)']],
				'php'       => ['8.1'],
			],
			$result
		);
	}

	public function testWithCompactFalseEachEnvironmentIsItsOwnEntry(): void
	{
		$result = $this->host->getParsedPlatforms($this->item([1, 2, 3]), false, false);

		self::assertSame(
			[
				'platforms' => [
					['joomla', '4.2'],
					['joomla', '4.3'],
				],
				'php'       => ['8.1'],
			],
			$result
		);
	}

	public function testLiarModeOffLeavesThePlatformListUntouched(): void
	{
		$withLiar    = $this->host->getParsedPlatforms($this->item([1, 2, 3]), false, true);
		$withoutLiar = $this->host->getParsedPlatforms($this->item([1, 2, 3]), false, false);

		self::assertNotSame($withLiar, $withoutLiar, 'Liar mode should add invented platform entries.');
		self::assertSame(
			[
				['joomla', '4.2'],
				['joomla', '4.3'],
			],
			$withoutLiar['platforms']
		);
	}

	/**
	 * "Liar mode" invents a next-minor entry for every real Joomla platform plus minor versions 0–10
	 * of the following major, so old update streams keep matching just-released Joomla versions they
	 * were never explicitly told about.
	 *
	 * Regression test for the `array_unique($platforms, SORT_REGULAR)` fix: SORT_REGULAR used to compare
	 * the two-element `['joomla', $version]` arrays with PHP's loose `==`, which for the version *strings*
	 * fell back to numeric comparison because both look numeric — and `'5.1' == '5.10'` is TRUE in PHP
	 * (both reduce to the float 5.1). That silently discarded `5.10` as a "duplicate" of `5.1`, so the
	 * minor-version sweep the code comments describe as "0–10" only ever produced "0–9". Deduplication is
	 * now done with an explicit string-keyed map, so the compacted RegEx below correctly reaches `10`.
	 */
	public function testLiarModeCompactedOutputCoversTheFullZeroToTenMinorRange(): void
	{
		$result = $this->host->getParsedPlatforms($this->item([1, 2, 3]), true, true);

		self::assertSame(
			[
				'platforms' => [['joomla', '((4\\.(2|3|4))|(5\\.(0|1|2|3|4|5|6|7|8|9|10)))']],
				'php'       => ['8.1'],
			],
			$result
		);
	}

	/**
	 * Regression test for commit f43eddd5 ("Fix undefined array key warning when platform has no minor
	 * version", closes #242). An environment declared as a major-only version (e.g. `joomla/4`, which
	 * `EnvironmentTable::onBeforeCheck()` legitimately accepts) used to make liar mode's
	 * `[$major, $minor] = explode('.', $item[1]);` destructure a one-element array, emitting an
	 * "Undefined array key 1" PHP warning that could escalate to a 500 error under strict error
	 * handling.
	 *
	 * This test installs its own error handler that turns any E_WARNING into a thrown ErrorException,
	 * so a regression fails loudly here instead of being silently swallowed. Verified to fail red
	 * against the pre-fix code (`explode()` without `array_pad()`) and pass green against the current
	 * fix, by temporarily reverting component/frontend/src/View/Update/Common.php to its parent commit.
	 */
	public function testAPlatformWithNoMinorVersionDoesNotWarn(): void
	{
		$this->host->envs = [
			1 => 'joomla/4',
			2 => 'php/8.1',
		];

		set_error_handler(static function (int $errno, string $errstr): bool {
			throw new \ErrorException($errstr, 0, $errno);
		}, E_WARNING);

		try
		{
			$result = $this->host->getParsedPlatforms($this->item([1, 2]), true, true);
		}
		finally
		{
			restore_error_handler();
		}

		self::assertSame(['joomla', '((4)|(5\\.(0|1|2|3|4|5|6|7|8|9|10)))'], $result['platforms'][0]);
	}

	public function testAPlatformWithNoMinorVersionAndLiarModeOffIsUnaffected(): void
	{
		$this->host->envs = [
			1 => 'joomla/4',
			2 => 'php/8.1',
		];

		$result = $this->host->getParsedPlatforms($this->item([1, 2]), true, false);

		self::assertSame(
			[
				'platforms' => [['joomla', '4']],
				'php'       => ['8.1'],
			],
			$result
		);
	}

	public function testANullItemReturnsAnEmptyStructureWithoutTouchingEnvs(): void
	{
		self::assertSame(
			['platforms' => [], 'php' => []],
			$this->host->getParsedPlatforms(null, true, false)
		);
	}
}
