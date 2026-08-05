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
 *    getReturnUrl() additionally screens the decoded payload for control characters (`\x00`-`\x1F`, `\x7F`) before
 *    ever calling {@see Uri::isInternal()}. A decoded payload containing a NUL byte or a CR/LF is rejected outright,
 *    since it would otherwise be a header-injection primitive once handed to setRedirect().
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
	 * rather than yielding false. For this particular garbage string, the decoded bytes happen to include a
	 * control character (0x1E), so the control-character guard in getReturnUrl() rejects it before
	 * Uri::isInternal() is ever consulted.
	 */
	public function testMalformedBase64DecodesToGarbageThatIsRejected(): void
	{
		$controller = $this->controllerWithInput([
			'return' => 'not valid base64!!!',
		]);

		$decoded = base64_decode('not valid base64!!!');
		$this->assertMatchesRegularExpression('/[\x00-\x1F\x7F]/', $decoded, 'Precondition: the decoded garbage must contain a control character for this test to be meaningful.');

		$this->assertNull($controller->callGetReturnUrl());
	}

	/**
	 * A NUL byte in the decoded payload is rejected by the control-character guard before Uri::isInternal() is
	 * ever consulted, since a NUL byte in a redirect target is a header-injection primitive.
	 */
	public function testNulByteInDecodedPayloadIsRejected(): void
	{
		$payload    = "index.php?a=1\0evil";
		$controller = $this->controllerWithInput([
			'return' => base64_encode($payload),
		]);

		$this->assertNull($controller->callGetReturnUrl());
	}

	/**
	 * An embedded newline (CR/LF) in the decoded payload is rejected by the control-character guard — this is the
	 * classic header-injection primitive if ever written into an HTTP header without further sanitisation
	 * downstream, so getReturnUrl() must reject it outright.
	 */
	public function testNewlineInDecodedPayloadIsRejected(): void
	{
		$payload    = "index.php?a=1\nSet-Cookie: x=y";
		$controller = $this->controllerWithInput([
			'return' => base64_encode($payload),
		]);

		$this->assertNull($controller->callGetReturnUrl());
	}
}
