<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Plugin\Content\ARSLatest;

defined('_JEXEC') or die;

use Akeeba\Plugin\Content\ARSLatest\Extension\Arslatest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Regression coverage for the M8 fix: `{arslatest RELEASE ...}`/`{arslatest STREAM_RELEASE ...}` tags in
 * a com_content article replaced themselves with the release `version` column verbatim, with no
 * escaping. `version` is free text, editable by any delegated Release Manager, so a crafted version
 * string became stored XSS for any visitor viewing the article. See `security.md`.
 *
 * `parseRelease()`/`parseStreamRelease()` read from the plugin's own `$categoryLatest`/`$streamInfo`
 * caches, populated elsewhere by a database query — since those are private properties with no public
 * setter, this test writes them directly via Reflection, exactly as {@see AnalyzeStringTest} builds the
 * plugin with `newInstanceWithoutConstructor()` to reach a method with no dependency on the rest of the
 * plugin (database, application, MVC factory).
 */
#[CoversClass(Arslatest::class)]
class ParseReleaseTest extends TestCase
{
	private Arslatest $plugin;

	protected function setUp(): void
	{
		$this->plugin = (new ReflectionClass(Arslatest::class))->newInstanceWithoutConstructor();
	}

	private function setPrivateProperty(string $property, $value): void
	{
		$ref = new ReflectionProperty(Arslatest::class, $property);

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$ref->setValue($this->plugin, $value);
	}

	private function invoke(string $method, array $args = []): string
	{
		$ref = new ReflectionMethod(Arslatest::class, $method);

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($this->plugin, ...$args);
	}

	// ---------------------------------------------------------------------------------------------
	// parseRelease()
	// ---------------------------------------------------------------------------------------------

	public function testALegitimateVersionIsReturnedUnchanged(): void
	{
		$this->setPrivateProperty('categoryLatest', [5 => (object) ['version' => '1.2.3']]);

		$this->assertSame('1.2.3', $this->invoke('parseRelease', ['5']));
	}

	public function testAMaliciousVersionIsHtmlEscaped(): void
	{
		$payload = '"><script>alert(1)</script>';

		$this->setPrivateProperty('categoryLatest', [5 => (object) ['version' => $payload]]);

		$this->assertSame(
			htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'),
			$this->invoke('parseRelease', ['5'])
		);
		$this->assertStringNotContainsString('<script>', $this->invoke('parseRelease', ['5']));
	}

	public function testNoMatchingReleaseReturnsAnEmptyString(): void
	{
		$this->setPrivateProperty('categoryLatest', []);

		$this->assertSame('', $this->invoke('parseRelease', ['5']));
	}

	// ---------------------------------------------------------------------------------------------
	// parseStreamRelease()
	// ---------------------------------------------------------------------------------------------

	public function testALegitimateStreamVersionIsReturnedUnchanged(): void
	{
		$this->setPrivateProperty('streamInfo', [5 => ['ALL' => (object) ['version' => '4.5.6']]]);

		$this->assertSame('4.5.6', $this->invoke('parseStreamRelease', ['5', 'ALL']));
	}

	public function testAMaliciousStreamVersionIsHtmlEscaped(): void
	{
		$payload = '"><script>alert(2)</script>';

		$this->setPrivateProperty('streamInfo', [5 => ['ALL' => (object) ['version' => $payload]]]);

		$this->assertSame(
			htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'),
			$this->invoke('parseStreamRelease', ['5', 'ALL'])
		);
		$this->assertStringNotContainsString('<script>', $this->invoke('parseStreamRelease', ['5', 'ALL']));
	}

	public function testNoMatchingStreamReturnsAnEmptyString(): void
	{
		$this->setPrivateProperty('streamInfo', []);

		$this->assertSame('', $this->invoke('parseStreamRelease', ['5', 'ALL']));
	}
}
