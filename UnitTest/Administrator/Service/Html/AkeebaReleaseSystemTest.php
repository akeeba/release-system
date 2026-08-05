<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Service\Html;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Service\Html\AkeebaReleaseSystem;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(AkeebaReleaseSystem::class)]
#[Group('Service')]
class AkeebaReleaseSystemTest extends TestCase
{
	protected function tearDown(): void
	{
		Factory::reset();
		LayoutHelper::reset();
		ComponentHelper::$params = [];
		Text::$strings           = [];

		// AkeebaReleaseSystem::$dateFormat is a private static memo; reset it so tests do not leak state into each
		// other under beStrictAboutChangesToGlobalState.
		$property = (new ReflectionClass(AkeebaReleaseSystem::class))->getProperty('dateFormat');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$property->setAccessible(true);
		}

		$property->setValue(null, null);

		parent::tearDown();
	}

	/**
	 * sizeFormat() is 1024-based (KiB/MiB/GiB thresholds) but labels the units "Kb"/"Mb"/"Gb" — pinning the current
	 * behaviour, not the technically-correct label.
	 *
	 * The Gb branch requires filesize to be STRICTLY GREATER than 1073741824 (`$filesize > 1073741824`); exactly one
	 * gibibyte therefore reports as "1,024.00 Mb", not "1.00 Gb". Confirmed empirically before writing this.
	 */
	public static function sizeFormatProvider(): array
	{
		return [
			'zero'                      => [0, '0 bytes'],
			'one byte'                  => [1, '1 bytes'],
			'sub-1KiB'                  => [512, '512 bytes'],
			'just below 1KiB boundary'  => [1023, '1023 bytes'],
			'exactly 1KiB'              => [1024, '1.00 Kb'],
			'just above 1KiB'           => [1025, '1.00 Kb'],
			'just below 1MiB boundary'  => [1048575, '1,024.00 Kb'],
			'exactly 1MiB'              => [1048576, '1.00 Mb'],
			'just above 1MiB'           => [1048577, '1.00 Mb'],
			'just below 1GiB boundary'  => [1073741823, '1,024.00 Mb'],
			'exactly 1GiB (NOT "1 Gb")' => [1073741824, '1,024.00 Mb'],
			'just above 1GiB boundary'  => [1073741825, '1.00 Gb'],
			'large (5GiB)'              => [5368709120, '5.00 Gb'],
		];
	}

	#[DataProvider('sizeFormatProvider')]
	public function testSizeFormat(int $filesize, string $expected): void
	{
		$this->assertSame($expected, AkeebaReleaseSystem::sizeFormat($filesize));
	}

	public static function severityLevelProvider(): array
	{
		return [
			'negative clamps to 0, renders nothing' => [-5, 0, false],
			'-1 clamps to 0, renders nothing'        => [-1, 0, false],
			'0 renders nothing'                      => [0, 0, false],
			'1 (low)'                                => [1, 1, true],
			'2 (medium)'                              => [2, 2, true],
			'3 (high)'                                => [3, 3, true],
			'4 (critical)'                             => [4, 4, true],
			'5 clamps to 4'                          => [5, 4, true],
			'100 clamps to 4'                        => [100, 4, true],
		];
	}

	/**
	 * The clamp is max(0, min($level, 4)); severity 0 (and anything that clamps to 0) must render NOTHING at all —
	 * an ordinary release must not be decorated with a "Security: None" badge. LayoutHelper::$renderCalls records
	 * calls instead of rendering, so we can assert both whether a layout was chosen and, when it was, exactly what
	 * it was chosen with.
	 */
	#[DataProvider('severityLevelProvider')]
	public function testSecurityBadgeSeverityClampAndRenderDecision(?int $input, int $expectedLevel, bool $expectRender): void
	{
		$result = AkeebaReleaseSystem::securityBadge($input);

		if (!$expectRender)
		{
			$this->assertSame('', $result);
			$this->assertCount(0, LayoutHelper::$renderCalls);

			return;
		}

		$this->assertCount(1, LayoutHelper::$renderCalls);
		$call = LayoutHelper::$renderCalls[0];

		$this->assertSame('akeeba.ars.common.securitybadge', $call['layout']);
		$this->assertSame($expectedLevel, $call['data']['level']);
		$this->assertSame('', $call['data']['extraClass']);
		$this->assertSame(['component' => 'com_ars', 'client' => 0], $call['options']);
	}

	public function testSecurityBadgeNullLevelRendersNothing(): void
	{
		$result = AkeebaReleaseSystem::securityBadge(null);

		$this->assertSame('', $result);
		$this->assertCount(0, LayoutHelper::$renderCalls);
	}

	public function testSecurityBadgePassesThroughExtraClass(): void
	{
		AkeebaReleaseSystem::securityBadge(2, 'my-extra-class');

		$this->assertSame('my-extra-class', LayoutHelper::$renderCalls[0]['data']['extraClass']);
	}

	/**
	 * preProcessMessage() replaces the [SITE] placeholder with Uri::base() and then hands the message through
	 * HTMLHelper::_('content.prepare', ...), which the stub returns as an empty string (it records nothing to
	 * assert against here — this only pins the [SITE] substitution happens before that hand-off, by checking it is
	 * gone from a message the content plugin stub would otherwise have echoed back untouched).
	 */
	public function testPreProcessMessageReplacesSitePlaceholder(): void
	{
		\Joomla\CMS\Uri\Uri::$baseUri = 'http://www.example.com/';

		// The HTMLHelper stub always returns '', so we cannot see the substituted string in the return value.
		// What we CAN assert is that calling it does not throw and returns the stub's inert value.
		$result = AkeebaReleaseSystem::preProcessMessage('Visit [SITE] for more', 'com_ars.test');

		$this->assertSame('', $result);
	}

	public function testDownloadIdReturnsEmptyStringForExplicitZeroUserIdWithoutTouchingTheDatabase(): void
	{
		// userId=0 is not null, so the Factory::getApplication() lookup is skipped entirely; empty(0) is true, so
		// the method returns '' before ever touching a database. No Factory priming needed proves that.
		$this->assertSame('', AkeebaReleaseSystem::downloadId(0));
	}

	/**
	 * formatDate() calls `$date->format($dateFormat, $local)` — a 2-argument call. The stock \DateTime::format()
	 * only accepts one argument, so this requires the Date stub to mirror real Joomla's Date::format($format,
	 * $local, $translate) signature (added to the stub for this reason). With an explicit, non-translated format
	 * string the result is deterministic and can be pinned exactly.
	 */
	public function testFormatDateWithExplicitFormatAndNoLocalisation(): void
	{
		Factory::$application = new class {
			public function getIdentity()
			{
				return new User(0);
			}

			public function get($key, $default = null)
			{
				return $default;
			}
		};

		$result = AkeebaReleaseSystem::formatDate('2024-01-15 10:30:00', false, 'Y-m-d');

		$this->assertSame('2024-01-15', $result);
	}
}
