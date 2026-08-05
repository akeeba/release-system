<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerReturnURLTrait;
use Joomla\CMS\Uri\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * getReturnUrl() is an open-redirect surface: it base64-decodes a `return`/`returnurl` request parameter and only
 * trusts the result if {@see Uri::isInternal()} — implemented for real in the stub, not faked away — says the
 * decoded string is internal to this site.
 *
 * Several cases here pin behaviour that is at best surprising, confirmed empirically rather than assumed:
 *  - When BOTH `return` and `returnurl` are supplied, `returnurl` wins (it is read second, using the `return`
 *    value only as ITS default).
 *  - Because {@see Uri::isInternal()}'s fallback rule is "internal unless it has a URL scheme or starts with `//`",
 *    a decoded payload containing a NUL byte or a newline — as long as it doesn't happen to look like an absolute
 *    URL — is treated as INTERNAL and returned as-is. Nothing in this call chain strips control characters.
 */
#[CoversClass(ControllerReturnURLTrait::class)]
#[Group('Mixin')]
class ControllerReturnURLTraitTest extends TestCase
{
	private $savedRootUri;

	private $savedBaseUri;

	protected function setUp(): void
	{
		parent::setUp();

		$this->savedRootUri = Uri::$rootUri;
		$this->savedBaseUri = Uri::$baseUri;

		Uri::$rootUri = 'http://www.example.com/';
		Uri::$baseUri = 'http://www.example.com/';
	}

	protected function tearDown(): void
	{
		Uri::$rootUri = $this->savedRootUri;
		Uri::$baseUri = $this->savedBaseUri;

		parent::tearDown();
	}

	private function controllerWithInput(array $base64EncodedByParam): object
	{
		$input = new class($base64EncodedByParam) {
			public function __construct(private array $data)
			{
			}

			public function getBase64($name, $default = null)
			{
				return $this->data[$name] ?? $default;
			}
		};

		$controller = new class {
			use ControllerReturnURLTrait;

			public $input;

			public function callGetReturnUrl(): ?string
			{
				$ref = new ReflectionMethod($this, 'getReturnUrl');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this);
			}
		};

		$controller->input = $input;

		return $controller;
	}

	public function testNoInputReturnsNull(): void
	{
		$controller = $this->controllerWithInput([]);

		$this->assertNull($controller->callGetReturnUrl());
	}

	public function testValidInternalRelativeUrlIsReturned(): void
	{
		$controller = $this->controllerWithInput([
			'return' => base64_encode('index.php?option=com_ars'),
		]);

		$this->assertSame('index.php?option=com_ars', $controller->callGetReturnUrl());
	}

	public function testValidInternalAbsoluteUrlIsReturned(): void
	{
		$controller = $this->controllerWithInput([
			'return' => base64_encode('http://www.example.com/index.php?option=com_ars'),
		]);

		$this->assertSame('http://www.example.com/index.php?option=com_ars', $controller->callGetReturnUrl());
	}

	public function testAbsoluteExternalUrlIsRejected(): void
	{
		$controller = $this->controllerWithInput([
			'return' => base64_encode('http://evil.test/'),
		]);

		$this->assertNull($controller->callGetReturnUrl());
	}

	public function testProtocolRelativeUrlIsRejected(): void
	{
		$controller = $this->controllerWithInput([
			'return' => base64_encode('//evil.test/'),
		]);

		$this->assertNull($controller->callGetReturnUrl());
	}

	public function testReturnurlParameterWinsWhenBothAreSupplied(): void
	{
		$controller = $this->controllerWithInput([
			'return'    => base64_encode('index.php?a=1'),
			'returnurl' => base64_encode('index.php?a=2'),
		]);

		$this->assertSame('index.php?a=2', $controller->callGetReturnUrl());
	}

	public function testFallsBackToReturnWhenReturnurlIsAbsent(): void
	{
		$controller = $this->controllerWithInput([
			'return' => base64_encode('index.php?a=1'),
		]);

		$this->assertSame('index.php?a=1', $controller->callGetReturnUrl());
	}

	/**
	 * base64_decode() is called WITHOUT strict mode, so malformed input decodes leniently into binary garbage
	 * rather than yielding false. That garbage has no URL scheme and does not start with `//`, so
	 * Uri::isInternal()'s fallback rule treats it as "internal" and it is returned unchanged. Pinning this exact,
	 * slightly alarming chain of defaults rather than assuming malformed input is rejected.
	 */
	public function testMalformedBase64DecodesToGarbageThatIsStillTreatedAsInternal(): void
	{
		$controller = $this->controllerWithInput([
			'return' => 'not valid base64!!!',
		]);

		$result = $controller->callGetReturnUrl();

		$this->assertNotNull($result, 'Garbage without a URL scheme or a leading // is treated as internal by Uri::isInternal().');
		$this->assertSame(base64_decode('not valid base64!!!'), $result);
	}

	/**
	 * SUSPECTED WEAKNESS, pinned rather than assumed fixed: a NUL byte survives the whole chain (base64 decode,
	 * Uri::isInternal()) and is handed back as the "internal" return URL. Uri::isInternal() only screens for a URL
	 * scheme or a leading "//"; it does not screen for control characters.
	 */
	public function testNulByteInDecodedPayloadIsNotRejected(): void
	{
		$payload    = "index.php?a=1\0evil";
		$controller = $this->controllerWithInput([
			'return' => base64_encode($payload),
		]);

		$this->assertSame($payload, $controller->callGetReturnUrl());
	}

	/**
	 * SUSPECTED WEAKNESS, pinned rather than assumed fixed: same as the NUL byte case, but with an embedded
	 * newline — the kind of payload that becomes a header-injection primitive if ever written into an HTTP header
	 * without further sanitisation downstream.
	 */
	public function testNewlineInDecodedPayloadIsNotRejected(): void
	{
		$payload    = "index.php?a=1\nSet-Cookie: x=y";
		$controller = $this->controllerWithInput([
			'return' => base64_encode($payload),
		]);

		$this->assertSame($payload, $controller->callGetReturnUrl());
	}
}
