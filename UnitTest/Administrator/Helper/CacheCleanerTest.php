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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * getAppConfigParam() is a private static five-branch fallback chain:
 *
 *  1. $app instanceof AbstractApplication          -> $app->get($key, $default)
 *  2. $app instanceof ConfigurationAwareApplicationInterface -> $app->get($key, $default)
 *  3. $app has a duck-typed get() method            -> $app->get($key, $default)
 *  4. Factory::getConfig() yields a Registry         -> (see SUSPECTED BUG below)
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
	 * SUSPECTED BUG, pinned rather than silently treated as correct — see CacheCleaner::getAppConfigParam():
	 *
	 *     if (method_exists(Factory::class, 'getConfig'))
	 *     {
	 *         try
	 *         {
	 *             $jConfig = Factory::getConfig();
	 *             if (is_object($jConfig) && ($jConfig instanceof Registry))
	 *             {
	 *                 $jConfig->get($key, $default);   // <-- return value is never used
	 *             }
	 *         }
	 *         catch (Throwable $e) { }
	 *     }
	 *
	 * The value read from Factory::getConfig() is computed and then discarded — there is no `return` in that
	 * branch. Priming Factory::$config with a Registry that DOES contain the requested key therefore has NO effect
	 * on the outcome: execution always falls through past it to the `JConfig` last-resort branch (or, if that also
	 * fails, to the plain $default). Confirmed empirically below: even with the key present in Factory's config,
	 * the method still returns the fallback default, not the value that was primed.
	 */
	public function testSuspectedBugFactoryGetConfigValueIsDiscardedAndNeverInfluencesTheResult(): void
	{
		Factory::$config = new Registry(['cache_path' => '/factory/config/path']);

		$result = $this->call(null, 'cache_path', 'DEFAULT');

		$this->assertNotSame('/factory/config/path', $result, 'The primed Factory::getConfig() value should have won here but the discarded return means it never can.');
		$this->assertSame('DEFAULT', $result);
	}
}
