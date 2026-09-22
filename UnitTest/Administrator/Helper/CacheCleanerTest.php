<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Helper;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\CacheCleaner;
use Joomla\Application\AbstractApplication;
use Joomla\Application\ConfigurationAwareApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * getAppConfigParam() is a private static five-branch fallback chain:
 *
 *  1. $app instanceof AbstractApplication          -> $app->get($key, $default)
 *  2. $app instanceof ConfigurationAwareApplicationInterface -> $app->get($key, $default)
 *  3. $app has a duck-typed get() method            -> $app->get($key, $default)
 *  4. Factory::getConfig() yields a Registry         -> $jConfig->get($key, $default)
 *  5. A last-resort `new JConfig()` loaded into a fresh Registry
 *
 * Branch 5 is reachable in this suite specifically because the Joomla stubs define JConfig unconditionally (see
 * joomla-stubs.php's docblock) — it is meant to be exercised, not skipped.
 */
#[CoversClass(CacheCleaner::class)]
#[Group('Helper')]
class CacheCleanerTest extends TestCase
{
	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	private function call($app, string $key, $default)
	{
		$ref = new ReflectionMethod(CacheCleaner::class, 'getAppConfigParam');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke(null, $app, $key, $default);
	}

	public function testAbstractApplicationBranchIsUsedFirst(): void
	{
		$app = new class extends AbstractApplication {
			public function get($key, $default = null)
			{
				return $key === 'cache_path' ? '/abstract/path' : $default;
			}
		};

		$this->assertSame('/abstract/path', $this->call($app, 'cache_path', 'DEFAULT'));
	}

	public function testConfigurationAwareApplicationInterfaceBranchIsUsedWhenNotAnAbstractApplication(): void
	{
		$app = new class implements ConfigurationAwareApplicationInterface {
			public function get($key, $default = null)
			{
				return $key === 'cache_path' ? '/config-aware/path' : $default;
			}
		};

		$this->assertSame('/config-aware/path', $this->call($app, 'cache_path', 'DEFAULT'));
	}

	public function testDuckTypedGetMethodBranchIsUsedAsALastResortForObjects(): void
	{
		$app = new class {
			public function get($key, $default = null)
			{
				return $key === 'cache_path' ? '/duck-typed/path' : $default;
			}
		};

		$this->assertSame('/duck-typed/path', $this->call($app, 'cache_path', 'DEFAULT'));
	}

	public function testNullAppFallsThroughToTheDefaultValue(): void
	{
		$this->assertSame('DEFAULT', $this->call(null, 'cache_path', 'DEFAULT'));
	}

	public function testObjectWithoutGetMethodOrRecognisedInterfaceFallsThroughToDefault(): void
	{
		$this->assertSame('DEFAULT', $this->call(new \stdClass(), 'cache_path', 'DEFAULT'));
	}

	/**
	 * Regression test for CacheCleaner::getAppConfigParam(): when there is no usable $app object, the method must
	 * fall back to Factory::getConfig() and return the value it holds for the requested key, rather than
	 * discarding it and falling through to the JConfig last-resort branch or the plain default.
	 */
	public function testFactoryGetConfigValueIsReturnedWhenPresent(): void
	{
		Factory::$config = new Registry(['cache_path' => '/factory/config/path']);

		$result = $this->call(null, 'cache_path', 'DEFAULT');

		$this->assertSame('/factory/config/path', $result);
	}

	/**
	 * @return array<string, array{0: int, 1: int, 2: string|null, 3: string}>
	 */
	public static function provideEveryClientCombination(): array
	{
		$cases = [];

		foreach (['site' => 0, 'back-end' => 1] as $currentName => $current)
		{
			foreach (['site' => 0, 'back-end' => 1] as $requestedName => $requested)
			{
				$cases["from the $currentName, the $requestedName cache, default path"] = [$current, $requested, null, JPATH_CACHE];
				$cases["from the $currentName, the $requestedName cache, custom path"]  = [$current, $requested, '/custom/cache', '/custom/cache'];
			}
		}

		return $cases;
	}

	/**
	 * Since Joomla 4.0 there is ONE cache directory for every client: each application (site, administrator,
	 * API, CLI) defines JPATH_CACHE as administrator/cache, and configuration.php's cache_path, when set, is
	 * shared by all of them. Verified in the Joomla 4.0.0, 4.4.14, 5.4.8 and 6.1.3 sources; only Joomla 3's site
	 * used its own cache/ folder.
	 *
	 * Regression: clearCacheGroup() used to send "the other client" to a hard-coded, inverted
	 * `($client_id) ? JPATH_SITE : JPATH_ADMINISTRATOR` (client 0 is the site).
	 */
	#[DataProvider('provideEveryClientCombination')]
	public function testEveryClientIsCleanedInTheOneSharedCacheDirectory(int $current, int $requested, ?string $cachePath, string $expected): void
	{
		$created = new \ArrayObject();

		$factory = new class($created) {
			public function __construct(private \ArrayObject $created)
			{
			}

			public function createCacheController($type, $options)
			{
				$this->created[] = $options;

				return new class {
					public object $cache;

					public function __construct()
					{
						$this->cache = new class {
							public function clean(): void
							{
							}
						};
					}
				};
			}
		};

		Factory::$container = new class($factory) {
			public function __construct(private object $factory)
			{
			}

			public function get($id)
			{
				return $id === 'cache.controller.factory' ? $this->factory : null;
			}
		};

		$app = new class($current, $cachePath) extends AbstractApplication {
			public function __construct(private int $clientId, private ?string $cachePath)
			{
			}

			public function getClientId(): int
			{
				return $this->clientId;
			}

			public function get($key, $default = null)
			{
				return $key === 'cache_path' && $this->cachePath !== null ? $this->cachePath : $default;
			}
		};

		CacheCleaner::clearCacheGroup('_system', $requested, $app);

		$this->assertSame($expected, $created[0]['cachebase']);
	}

	/**
	 * Failing to clean one cache must not stop the rest being cleaned: every later client and group would
	 * otherwise be left serving stale data.
	 *
	 * Regression: clearCacheGroups() used to RETURN on the first failure instead of continuing.
	 */
	public function testClearCacheGroupsCarriesOnAfterAFailure(): void
	{
		$factory = new class {
			public int $calls = 0;

			public int $cleaned = 0;

			public function createCacheController($type, $options)
			{
				// The first call fails, as a broken cache handler would.
				if ($this->calls++ === 0)
				{
					throw new \RuntimeException('Simulated cache failure');
				}

				$owner = $this;

				return new class($owner) {
					public object $cache;

					public function __construct(object $owner)
					{
						$this->cache = new class($owner) {
							public function __construct(private object $owner)
							{
							}

							public function clean(): void
							{
								$this->owner->cleaned++;
							}
						};
					}
				};
			}
		};

		Factory::$container = new class($factory) {
			public function __construct(private object $factory)
			{
			}

			public function get($id)
			{
				return $id === 'cache.controller.factory' ? $this->factory : null;
			}
		};

		Factory::$application = new class extends AbstractApplication {
			public function __construct()
			{
			}

			public function get($key, $default = null)
			{
				return $default;
			}
		};

		CacheCleaner::clearCacheGroups(['_system', 'com_ars'], [0, 1]);

		$this->assertSame(4, $factory->calls, 'Every client of every group must be attempted.');
		$this->assertSame(3, $factory->cleaned, 'Everything after the failure must still be cleaned.');
	}
}
