<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\ModernRecordingDatabase;
use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\AutodescriptionsModel;
use Akeeba\Component\ARS\Administrator\Model\CategoriesModel;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelsModel;
use Akeeba\Component\ARS\Administrator\Model\EnvironmentsModel;
use Akeeba\Component\ARS\Administrator\Model\ItemsModel;
use Akeeba\Component\ARS\Administrator\Model\LogsModel;
use Akeeba\Component\ARS\Administrator\Model\ReleasesModel;
use Akeeba\Component\ARS\Administrator\Model\UpdatestreamsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Regression coverage for the ORDER BY / tag-IN() hardening from commit 139d02c2.
 *
 * Before that commit, every one of these eight list models built its ORDER BY clause with
 * `$db->escape($orderCol) . ' ' . $db->escape($orderDirn)` — escape() is a VALUE escaper, not an identifier quoter,
 * misapplied to both the column name and (nonsensically) the direction keyword. After it, the column goes through
 * quoteName() and the direction is constrained to a literal ASC/DESC whitelist.
 *
 * {@see RecordingDatabase::quoteName()} back-ticks whatever it is given, so a back-tick surviving into the recorded
 * ORDER BY proves quoteName() ran (escape() would not have added one). Every case runs against both
 * {@see RecordingDatabase} and {@see ModernRecordingDatabase} because ARS branches on
 * `method_exists($db, 'createQuery')`, which is answered by the class, not the instance — a test against only one
 * of them would prove only the branch it happened to exercise.
 */
#[CoversClass(AutodescriptionsModel::class)]
#[CoversClass(CategoriesModel::class)]
#[CoversClass(DlidlabelsModel::class)]
#[CoversClass(EnvironmentsModel::class)]
#[CoversClass(ItemsModel::class)]
#[CoversClass(LogsModel::class)]
#[CoversClass(ReleasesModel::class)]
#[CoversClass(UpdatestreamsModel::class)]
#[Group('Model')]
class ListModelOrderingTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// DlidlabelsModel::getListQuery() reads Factory::getApplication()->isClient('site') up front, and (in the
		// site branch only, which this fake avoids) getIdentity(). Every other model here ignores the application
		// entirely, so one fake app safely covers all eight.
		Factory::$application = new class {
			public function isClient($client)
			{
				return false; // Pretend to be the backend for every model.
			}

			public function getIdentity()
			{
				return new User(0);
			}
		};
	}

	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	public static function modelAndDatabaseProvider(): array
	{
		$models = [
			'AutodescriptionsModel' => AutodescriptionsModel::class,
			'CategoriesModel'       => CategoriesModel::class,
			'DlidlabelsModel'       => DlidlabelsModel::class,
			'EnvironmentsModel'     => EnvironmentsModel::class,
			'ItemsModel'            => ItemsModel::class,
			'LogsModel'             => LogsModel::class,
			'ReleasesModel'         => ReleasesModel::class,
			'UpdatestreamsModel'    => UpdatestreamsModel::class,
		];

		$databases = [
			'RecordingDatabase (pre-5.1 getQuery() branch)' => RecordingDatabase::class,
			'ModernRecordingDatabase (5.1+ createQuery() branch)' => ModernRecordingDatabase::class,
		];

		$cases = [];

		foreach ($models as $modelLabel => $modelClass)
		{
			foreach ($databases as $dbLabel => $dbClass)
			{
				$cases["$modelLabel / $dbLabel"] = [$modelClass, $dbClass];
			}
		}

		return $cases;
	}

	/**
	 * @param class-string<ListModel> $modelClass
	 * @param class-string            $dbClass
	 */
	private function buildQuery(string $modelClass, string $dbClass, string $ordering, string $direction): object
	{
		/** @var RecordingDatabase $db */
		$db = new $dbClass();

		/** @var ListModel $model */
		$model = new $modelClass([], null);
		$model->setDatabase($db);
		$model->setState('list.ordering', $ordering);
		$model->setState('list.direction', $direction);

		// DlidlabelsModel additionally reads filter.dlid directly with strpos() before any null-coalescing; leaving
		// it unset triggers a "Passing null to parameter #1 ($haystack)" deprecation. Every other model tolerates
		// an unset filter state fine.
		$model->setState('filter.dlid', '');

		$ref = new ReflectionMethod($modelClass, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($model);
	}

	#[DataProvider('modelAndDatabaseProvider')]
	public function testHostileOrderingColumnIsQuoteNamedNotEscaped(string $modelClass, string $dbClass): void
	{
		$query = $this->buildQuery($modelClass, $dbClass, 'evil`col', 'ASC');

		// quoteName() back-ticks the identifier; escape() (the pre-hardening code) never would have.
		$this->assertStringStartsWith('`evil`col`', $query->firstOrder());
	}

	#[DataProvider('modelAndDatabaseProvider')]
	public function testHostileDirectionDoesNotSurviveIntoTheQuery(string $modelClass, string $dbClass): void
	{
		$query = $this->buildQuery($modelClass, $dbClass, 'id', 'ASC; DROP TABLE x;--');

		// Whatever the injected direction was, it must have been reduced to exactly ASC or DESC.
		$order = $query->firstOrder();
		$this->assertMatchesRegularExpression('/ (ASC|DESC)$/', $order);
		$this->assertStringNotContainsString('DROP TABLE', $order);
	}

	#[DataProvider('modelAndDatabaseProvider')]
	public function testUnrecognisedDirectionDefaultsToAscending(string $modelClass, string $dbClass): void
	{
		$query = $this->buildQuery($modelClass, $dbClass, 'id', 'ASC; DROP TABLE x;--');

		$this->assertStringEndsWith(' ASC', $query->firstOrder());
	}

	#[DataProvider('modelAndDatabaseProvider')]
	public function testLowercaseDescIsWhitelistedToUppercaseDesc(string $modelClass, string $dbClass): void
	{
		$query = $this->buildQuery($modelClass, $dbClass, 'id', 'desc');

		$this->assertStringEndsWith(' DESC', $query->firstOrder());
	}

	#[DataProvider('modelAndDatabaseProvider')]
	public function testDirectionThatOnlyStartsWithDescIsNotAccepted(string $modelClass, string $dbClass): void
	{
		// "DESC; DROP TABLE x" starts with DESC but is not literally DESC after strtoupper(); it must fall back
		// to the safe default (ASC), not be accepted as-is.
		$query = $this->buildQuery($modelClass, $dbClass, 'id', 'DESC; DROP TABLE x');

		$this->assertStringEndsWith(' ASC', $query->firstOrder());
	}

	public static function tagFilterModelProvider(): array
	{
		return [
			'CategoriesModel' => [CategoriesModel::class],
			'ReleasesModel'   => [ReleasesModel::class],
		];
	}

	/**
	 * The tag filter's `IN()` list is bound as integers (commit 139d02c2), replacing unbound string concatenation —
	 * and it is bound against the OUTER query, not against the sub-query that carries the `IN()` clause.
	 *
	 * That distinction is the whole test. The sub-query is never passed to setQuery(): it is embedded into the outer
	 * query's JOIN clause by string concatenation, and bound parameters do not survive that. DatabaseQuery::
	 * getBounded() only ever returns a query object's own bindings, so placeholders bound on the sub-query reach the
	 * driver unbound and the entire list view dies with "No data supplied for parameters in prepared statement" —
	 * for any filter of two or more tags, valid IDs included.
	 *
	 * Both query objects are observable via {@see RecordingDatabase::$createdQueries}, which records every query
	 * OBJECT created regardless of whether it was ever executed.
	 */
	#[DataProvider('tagFilterModelProvider')]
	public function testMultipleTagFilterBindsIdsAsIntegersOnTheOuterQuery(string $modelClass): void
	{
		$db = new RecordingDatabase();

		$model = new $modelClass([], null);
		$model->setDatabase($db);
		$model->setState('list.ordering', 'id');
		$model->setState('list.direction', 'ASC');
		$model->setState('filter.dlid', '');
		$model->setState('filter.tag', [3, '5']);

		$ref = new ReflectionMethod($modelClass, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$query = $ref->invoke($model);

		// createdQueries[0] is the outer query; createdQueries[1] is the tag sub-query.
		$this->assertCount(2, $db->createdQueries);
		$this->assertSame($query, $db->createdQueries[0]);

		$subQuery = $db->createdQueries[1];

		$this->assertSame(
			[],
			$subQuery->bindValues,
			'The sub-query must carry no bindings of its own; they would be lost when it is cast to a string.'
		);

		$tagBindings = array_filter(
			$query->bindValues,
			fn(string $name): bool => str_starts_with($name, ':preparedArray'),
			ARRAY_FILTER_USE_KEY
		);

		$this->assertSame([3, 5], array_values($tagBindings), 'Tag IDs must be bound, as integers.');

		foreach (array_keys($tagBindings) as $name)
		{
			$this->assertSame(\Joomla\Database\ParameterType::INTEGER, $query->bindTypes[$name]);

			// The names the outer query generated must be the ones inlined into the sub-query's IN() clause.
			$this->assertStringContainsString(
				$name,
				implode(' ', $subQuery->whereCalls),
				sprintf('The sub-query does not use the placeholder %s bound on the outer query.', $name)
			);
		}
	}

	/**
	 * A tag ID which is not a positive integer is dropped rather than pushed into the query, and a filter with
	 * nothing usable left in it is not applied at all.
	 *
	 * Malformed values arrive routinely, because `filter.tag` is populated from `getUserStateFromRequest()`: whatever
	 * lands in it stays in the session, so a value the query cannot survive would break the list view on every later
	 * request in that session, including ones which never mention the tag filter.
	 */
	#[DataProvider('tagFilterModelProvider')]
	public function testTagFilterDiscardsValuesThatAreNotTagIds(string $modelClass): void
	{
		$ref = new ReflectionMethod($modelClass, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		// One usable ID among the junk: the junk is dropped, the ID is used — and one ID takes the single-tag path.
		$db    = new RecordingDatabase();
		$model = new $modelClass([], null);
		$model->setDatabase($db);
		$model->setState('list.ordering', 'id');
		$model->setState('list.direction', 'ASC');
		$model->setState('filter.dlid', '');
		$model->setState('filter.tag', ['DROP TABLE x', '7']);

		$query = $ref->invoke($model);

		$this->assertCount(1, $db->createdQueries, 'A single surviving tag ID must not build a sub-query.');
		$this->assertSame(7, $query->bindValues[':tagId']);

		// Nothing usable at all: no join, no bindings, no filter.
		$db    = new RecordingDatabase();
		$model = new $modelClass([], null);
		$model->setDatabase($db);
		$model->setState('list.ordering', 'id');
		$model->setState('list.direction', 'ASC');
		$model->setState('filter.dlid', '');
		$model->setState('filter.tag', ['DROP TABLE x', 'nonsense', '0', '-1']);

		$query = $ref->invoke($model);

		$this->assertCount(1, $db->createdQueries, 'An unusable tag filter must not build a sub-query.');
		$this->assertArrayNotHasKey(':tagId', $query->bindValues);
		$this->assertStringNotContainsString(
			'contentitem_tag_map',
			json_encode($query->joinCalls),
			'An unusable tag filter must not join the tag map at all.'
		);
	}

	/**
	 * A single tag takes a different, simplified code path (a scalar bind rather than whereIn()); this pins that
	 * the optimisation does not accidentally reintroduce unbound concatenation either.
	 */
	#[DataProvider('tagFilterModelProvider')]
	public function testSingleTagFilterUsesABoundParameter(string $modelClass): void
	{
		$db = new RecordingDatabase();

		$model = new $modelClass([], null);
		$model->setDatabase($db);
		$model->setState('list.ordering', 'id');
		$model->setState('list.direction', 'ASC');
		$model->setState('filter.dlid', '');
		$model->setState('filter.tag', [7]);

		$ref = new ReflectionMethod($modelClass, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$query = $ref->invoke($model);

		$this->assertArrayHasKey(':tagId', $query->bindValues);
		$this->assertSame(7, $query->bindValues[':tagId']);
		$this->assertSame(\Joomla\Database\ParameterType::INTEGER, $query->bindTypes[':tagId']);
	}
}
