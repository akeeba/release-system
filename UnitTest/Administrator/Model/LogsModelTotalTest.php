<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\LogsModel;
use Joomla\CMS\Cache\Controller\CallbackController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the cached unfiltered row count in {@see LogsModel::getTotal()}.
 *
 * The download log is an append-only table with millions of rows, so an unfiltered COUNT(*) is a
 * full index scan the model cannot afford on every page view; the exact total is cached for a few
 * minutes instead. As soon as a filter is active the count query runs over the same indexed columns
 * as the list query, stays cheap, and must remain live and uncached.
 *
 * {@see FakeCallbackController} stands in for the real Joomla cache controller, which needs a
 * booted application and container the unit suite deliberately does not provide; the model hands it
 * over through the {@see LogsModel::getCountCacheController()} seam.
 */
#[CoversClass(LogsModel::class)]
#[Group('Model')]
class LogsModelTotalTest extends TestCase
{
	public static function activeFilterProvider(): array
	{
		return [
			'search'      => ['filter.search', '203.0.113.99'],
			'user_id'     => ['filter.user_id', 42],
			'referer'     => ['filter.referer', 'example.com'],
			'authorized'  => ['filter.authorized', 0],
		];
	}

	private function buildModel(FakeCallbackController $cache, RecordingDatabase $db): LogsModel
	{
		$model = new class([], null) extends LogsModel
		{
			public FakeCallbackController $countCache;

			protected function getCountCacheController(): CallbackController
			{
				return $this->countCache;
			}
		};
		$model->countCache = $cache;
		$model->setDatabase($db);

		return $model;
	}

	public function testUnfilteredTotalIsComputedOnceThenServedFromCache(): void
	{
		$db    = new RecordingDatabase();
		$db->result = 12345;
		$cache = new FakeCallbackController();
		$model = $this->buildModel($cache, $db);

		$this->assertSame(12345, $model->getTotal());
		$this->assertSame(1, $cache->callbackRuns, 'The count query must run exactly once.');
		$this->assertCount(1, $db->queries, 'The count query must hit the database exactly once.');

		// The table "grows" behind the cache; the cached total must stand.
		$db->result = 99999;

		$this->assertSame(12345, $model->getTotal());
		$this->assertSame(1, $cache->callbackRuns, 'A warm cache must not recount.');
		$this->assertCount(1, $db->queries, 'A warm cache must not re-query the database.');
	}

	#[DataProvider('activeFilterProvider')]
	public function testFilteredTotalBypassesTheCache(string $state, $value): void
	{
		$db    = new RecordingDatabase();
		$db->result = 7;
		$cache = new FakeCallbackController();
		$model = $this->buildModel($cache, $db);
		$model->setState($state, $value);

		$this->assertSame(7, $model->getTotal());
		$this->assertSame(0, $cache->callbackRuns, 'A filtered count must not touch the cache.');

		$db->result = 8;

		$this->assertSame(8, $model->getTotal());
		$this->assertSame(0, $cache->callbackRuns, 'A filtered count must stay live on every call.');
		$this->assertCount(2, $db->queries, 'A filtered count must re-query the database every time.');
	}

	public function testCleanCachedTotalForcesTheNextCallToRecount(): void
	{
		$db    = new RecordingDatabase();
		$db->result = 12345;
		$cache = new FakeCallbackController();
		$model = $this->buildModel($cache, $db);

		$model->getTotal();
		$model->cleanCachedTotal();

		$db->result = 99999;

		$this->assertSame(99999, $model->getTotal());
		$this->assertSame(2, $cache->callbackRuns);
	}
}

/**
 * In-memory stand-in for Joomla's callback cache controller.
 *
 * Mimics the semantics LogsModel relies on: get() runs the callback and remembers its result under
 * the given ID until remove() drops it. The signature mirrors Joomla\CMS\Cache\Controller\
 * CallbackController::get() so the double keeps working if a real cache implementation is ever
 * autoloadable in this suite.
 */
class FakeCallbackController extends CallbackController
{
	/** @var array<string, mixed> */
	public array $store = [];

	/** @var int How many times the wrapped callback has actually been executed. */
	public int $callbackRuns = 0;

	public function get($callback, $args = [], $id = false, $wrkarounds = false, $woptions = [])
	{
		if (array_key_exists($id, $this->store))
		{
			return $this->store[$id];
		}

		$this->callbackRuns++;

		return $this->store[$id] = $callback(...$args);
	}

	public function remove($id, $group = null)
	{
		unset($this->store[$id]);
	}
}
