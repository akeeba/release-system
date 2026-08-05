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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A test host for the {@see Common} trait.
 *
 * `Update\Common` is a trait, not a class: it has to be mixed into something before it can be
 * exercised. `HtmlView` (stubbed in UnitTest/Stubs/joomla-stubs.php) is the base ARS's own update
 * views build on, so it is what a test host uses too. `platformVersionCompactor()` is `protected`;
 * rather than reaching for Reflection on every call, this host exposes a thin public pass-through,
 * which is the "local test double" the task brief prefers over reflection gymnastics.
 */
class PlatformCompactorHost extends HtmlView
{
	use Common;

	public array $envs = [];

	public $category;

	public function compact(array $versions): string
	{
		return $this->platformVersionCompactor($versions);
	}
}

/**
 * Tests {@see Common::platformVersionCompactor()}, the RegEx-building algorithm that turns a list of
 * platform versions (e.g. `4.1`, `4.2`, `4.3`) into a single RegEx Joomla's updater matches the site's
 * `targetplatform` version against. It is the single richest pure algorithm in the codebase.
 */
#[CoversClass(Common::class)]
class PlatformVersionCompactorTest extends TestCase
{
	private PlatformCompactorHost $host;

	protected function setUp(): void
	{
		$this->host = new PlatformCompactorHost();
	}

	/**
	 * @return array<string,array{0:array<int,string>,1:string}>
	 */
	public static function provideOrdinaryVersions(): array
	{
		return [
			'a single version'                => [['4.2.1'], '4.2.1'],
			'several minors of one major'     => [['4.1', '4.2', '4.3'], '4\\.(1|2|3)'],
			'several majors'                  => [['3.10', '4.2'], '((3.10)|(4.2))'],
			'three-part versions'             => [['4.2.1', '4.2.2'], '((4.2.1)|(4.2.2))'],
			'a three-part version with a trailing star' => [['4.2.*'], '4.2'],
			'a bare major-only version'       => [['4'], '4'],
			'duplicates are not deduplicated' => [['4.1', '4.1', '4.2'], '4\\.(1|1|2)'],
			'unsorted input keeps insertion order, not numeric order' => [
				['4.3', '4.1', '3.9'],
				'((4\\.(3|1))|(3.9))',
			],
			'the empty array'                 => [[], '()'],
		];
	}

	#[DataProvider('provideOrdinaryVersions')]
	public function testItCompactsAListOfVersionsIntoARegex(array $versions, string $expected): void
	{
		self::assertSame($expected, $this->host->compact($versions));
	}

	/**
	 * A bare `*` entry (no major, no dot) is a documented shorthand: it means "match any Joomla
	 * version whatsoever". It reaches the compactor's `.*` special case via the ordinary per-major
	 * branch — `$byMajor['*'] = ['*']` — which is why it does not share the fate of the literal
	 * `.*` entry covered by {@see testALiteralDotStarEntryCurrentlyThrows()} below.
	 */
	public function testABareStarEntryMeansAnyVersion(): void
	{
		self::assertSame('.*', $this->host->compact(['*']));
	}

	/**
	 * An `x.*` entry — a major with an explicit "all minors" wildcard, e.g. `4.*` — compacts down to
	 * the bare major. Joomla's updater treats a bare major-version RegEx as "any minor of this
	 * major", so dropping the `.*` suffix is intentional, not lossy.
	 */
	public function testAMajorDotStarEntryCompactsToTheBareMajor(): void
	{
		self::assertSame('4', $this->host->compact(['4.*']));
	}

	/**
	 * A version entry consisting of `4.*` should also override any more specific minor already
	 * recorded for that major, in either order.
	 */
	public function testAMajorDotStarEntryOverridesASpecificMinorForTheSameMajor(): void
	{
		self::assertSame('4', $this->host->compact(['4.*', '4.1']));
		self::assertSame('4', $this->host->compact(['4.1', '4.*']));
	}

	/**
	 * SUSPECTED BUG. The inline comment directly above the `empty($major) && ($minor == '*')` branch
	 * in platformVersionCompactor() reads: "Did someone specify '.*'?! OK, we will tell Joomla to
	 * install no matter the version." — i.e. a literal `.*` entry is meant to force the whole result
	 * to the override `'.*'`, exactly like the bare `*` entry covered above.
	 *
	 * It does not. That branch sets `$byMajor = ['*' => '*']` — a flat string value — whereas every
	 * other branch of the method (including the bare-`*` path) stores `$byMajor[$major]` as an
	 * *array* of minor versions. The final loop then calls `count($minorVersions)` on that string and
	 * PHP 8 throws a TypeError, because a string is not Countable.
	 *
	 * In practice this is unreachable from real data: `EnvironmentTable::onBeforeCheck()` validates
	 * `xmltitle` versions against `/^(\d+\.){0,}\d+$/`, which admits digits and dots only — `*` can
	 * never reach this method through the front door. So this pins a genuine, if inert, defect rather
	 * than a live production bug. Per the task brief, current (buggy) behaviour is pinned here with
	 * this comment rather than silently asserted as correct; revisit this test if the method is ever
	 * fixed to honour its own comment.
	 */
	public function testALiteralDotStarEntryCurrentlyThrows(): void
	{
		$this->expectException(\TypeError::class);

		$this->host->compact(['.*']);
	}
}
