<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\GetPropertiesAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * getProperties() exploits a PHP quirk rather than using Reflection: casting an object to an array makes PHP prefix
 * private property names with `\0ClassName\0` and protected ones with `\0*\0` — a NUL byte is always the first
 * character. `$public = true` filters those out by checking `ord(substr($key, 0, 1)) !== 0` on the ARRAY KEYS
 * (array_filter is called with ARRAY_FILTER_USE_KEY), NOT on the property values. The same predicate also drops
 * keys that are empty or numeric, which — since PHP property names can never legally be empty or purely numeric —
 * is dead weight for this use case but is pinned here anyway as part of the actual predicate.
 */
#[CoversClass(GetPropertiesAwareTrait::class)]
#[Group('Mixin')]
class GetPropertiesAwareTraitTest extends TestCase
{
	private function makeSubject(): object
	{
		return new class {
			use GetPropertiesAwareTrait;

			public $publicValue = 'public-value';

			public $publicEmpty = '';

			public $publicZero = 0;

			protected $protectedValue = 'protected-value';

			private $privateValue = 'private-value';
		};
	}

	public function testPublicTrueExcludesProtectedAndPrivateProperties(): void
	{
		$properties = $this->makeSubject()->getProperties(true);

		$this->assertArrayHasKey('publicValue', $properties);
		$this->assertSame('public-value', $properties['publicValue']);

		foreach (array_keys($properties) as $key)
		{
			$this->assertStringNotContainsString('protectedValue', $key);
			$this->assertStringNotContainsString('privateValue', $key);
		}
	}

	/**
	 * The filter predicate checks the KEY, not the value — an empty-string or falsy-but-present public property
	 * value must still survive because its key ('publicEmpty', 'publicZero') is a normal non-empty, non-numeric
	 * string.
	 */
	public function testPublicTrueKeepsPublicPropertiesWithEmptyOrFalsyValues(): void
	{
		$properties = $this->makeSubject()->getProperties(true);

		$this->assertArrayHasKey('publicEmpty', $properties);
		$this->assertSame('', $properties['publicEmpty']);

		$this->assertArrayHasKey('publicZero', $properties);
		$this->assertSame(0, $properties['publicZero']);
	}

	public function testPublicFalseIncludesEverythingWithMangledKeysForNonPublicProperties(): void
	{
		$subject    = $this->makeSubject();
		$properties = $subject->getProperties(false);

		$this->assertArrayHasKey('publicValue', $properties);

		$mangledKeys = array_filter(array_keys($properties), static fn($key) => str_contains($key, 'protectedValue') || str_contains($key, 'privateValue'));

		$this->assertCount(2, $mangledKeys, 'Both the protected and private property must be present, keyed with their NUL-byte-prefixed mangled name.');

		foreach ($mangledKeys as $key)
		{
			$this->assertSame("\0", $key[0], 'A non-public property key must start with a NUL byte.');
		}
	}

	public function testPublicFalseValuesAreStillCorrect(): void
	{
		$properties = $this->makeSubject()->getProperties(false);

		foreach ($properties as $key => $value)
		{
			if (str_contains($key, 'protectedValue'))
			{
				$this->assertSame('protected-value', $value);
			}

			if (str_contains($key, 'privateValue'))
			{
				$this->assertSame('private-value', $value);
			}
		}
	}
}
