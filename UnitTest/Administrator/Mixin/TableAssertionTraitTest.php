<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use stdClass;

/**
 * assertNotInArray() uses STRICT `in_array(..., true)` while assertInArray() uses LOOSE `in_array()` (no third
 * argument). That asymmetry is pinned explicitly below: assertInArray() treats 0, '0' and false as interchangeable
 * (loose comparison), while assertNotInArray() does not conflate them (strict comparison).
 */
#[CoversClass(TableAssertionTrait::class)]
#[Group('Mixin')]
class TableAssertionTraitTest extends TestCase
{
	private function subject(): object
	{
		return new class {
			use TableAssertionTrait;

			public function callAssert($condition, $message)
			{
				$ref = new ReflectionMethod($this, 'assert');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this, $condition, $message);
			}

			public function callAssertNotEmpty($value, $message)
			{
				$ref = new ReflectionMethod($this, 'assertNotEmpty');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this, $value, $message);
			}

			public function callAssertInArray($value, array $validValues, $message)
			{
				$ref = new ReflectionMethod($this, 'assertInArray');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this, $value, $validValues, $message);
			}

			public function callAssertNotInArray($value, array $validValues, $message)
			{
				$ref = new ReflectionMethod($this, 'assertNotInArray');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this, $value, $validValues, $message);
			}
		};
	}

	public function testAssertPassesSilentlyWhenConditionIsTrue(): void
	{
		$this->subject()->callAssert(true, 'SOME_MESSAGE_KEY');
		$this->addToAssertionCount(1);
	}

	public function testAssertThrowsWhenConditionIsFalse(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('SOME_MESSAGE_KEY');

		$this->subject()->callAssert(false, 'SOME_MESSAGE_KEY');
	}

	public function testAssertNotEmptyPassesForNonEmptyValue(): void
	{
		$this->subject()->callAssertNotEmpty('hello', 'MSG');
		$this->addToAssertionCount(1);
	}

	public static function emptyValueProvider(): array
	{
		return [
			'empty string' => [''],
			'null'         => [null],
			'zero'         => [0],
			'false'        => [false],
			'empty array'  => [[]],
		];
	}

	#[DataProvider('emptyValueProvider')]
	public function testAssertNotEmptyThrowsForEmptyValues($value): void
	{
		$this->expectException(RuntimeException::class);

		$this->subject()->callAssertNotEmpty($value, 'MSG');
	}

	public function testAssertInArrayPassesWhenValuePresent(): void
	{
		$this->subject()->callAssertInArray('b', ['a', 'b', 'c'], 'MSG');
		$this->addToAssertionCount(1);
	}

	public function testAssertInArrayThrowsWhenValueAbsent(): void
	{
		$this->expectException(RuntimeException::class);

		$this->subject()->callAssertInArray('z', ['a', 'b', 'c'], 'MSG');
	}

	/**
	 * assertInArray() has NO third `in_array()` argument, so comparison is LOOSE: 0 == 'abc' is false, but
	 * 0 == '0' and 0 == false are both true under loose comparison. Pinning that 0 is treated as present in
	 * ['0', 'other'] even though it's a different type.
	 */
	public function testAssertInArrayUsesLooseComparison(): void
	{
		$this->subject()->callAssertInArray(0, ['0', 'other'], 'MSG');
		$this->addToAssertionCount(1);
	}

	public function testAssertNotInArrayPassesWhenValueAbsent(): void
	{
		$this->subject()->callAssertNotInArray('z', ['a', 'b', 'c'], 'MSG');
		$this->addToAssertionCount(1);
	}

	public function testAssertNotInArrayThrowsWhenValuePresent(): void
	{
		$this->expectException(RuntimeException::class);

		$this->subject()->callAssertNotInArray('b', ['a', 'b', 'c'], 'MSG');
	}

	/**
	 * The pinned asymmetry: assertNotInArray() uses STRICT comparison, so integer 0 is NOT considered "in" an array
	 * containing only the string '0' — the opposite of assertInArray()'s loose-comparison behaviour above.
	 */
	public function testAssertNotInArrayUsesStrictComparison(): void
	{
		// Must NOT throw: 0 (int) is not strictly equal to '0' (string) or 'other'.
		$this->subject()->callAssertNotInArray(0, ['0', 'other'], 'MSG');
		$this->addToAssertionCount(1);
	}

	public function testAssertNotInArrayStrictComparisonStillCatchesATrueDuplicate(): void
	{
		$this->expectException(RuntimeException::class);

		$this->subject()->callAssertNotInArray(0, [0, 'other'], 'MSG');
	}
}
