<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\ItemsModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Regression coverage for commit 409d70bf ("Fix Latest view error page when no release is visible").
 *
 * An empty `filter.release_id` ARRAY (as opposed to it not being set, or being a single numeric ID) used to reach
 * `whereIn()` unconditionally, producing an `IN ()` expression — a SQL syntax error the database rejects, turning
 * "no releases visible" into a hard error page instead of an empty result set.
 */
#[CoversClass(ItemsModel::class)]
#[Group('Model')]
class ItemsModelReleaseFilterTest extends TestCase
{
	private function buildQuery($releaseId): object
	{
		$db = new RecordingDatabase();

		$model = new ItemsModel([], null);
		$model->setDatabase($db);
		$model->setState('filter.release_id', $releaseId);

		$ref = new ReflectionMethod(ItemsModel::class, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($model);
	}

	public function testEmptyReleaseIdArrayProducesAlwaysFalseConditionNotWhereIn(): void
	{
		$query = $this->buildQuery([]);

		$this->assertSame([], $query->whereInCalls, 'An empty release_id filter must not reach whereIn() — that would emit IN ().');
		$this->assertContains('0 = 1', $query->whereCalls, 'An empty release_id filter must add an always-false condition instead.');
	}

	public function testNonEmptyReleaseIdArrayStillUsesWhereIn(): void
	{
		$query = $this->buildQuery([1, 2, 3]);

		$this->assertNotContains('0 = 1', $query->whereCalls);
		$this->assertCount(1, $query->whereInCalls);

		[$column, $values] = $query->whereInCalls[0];
		$this->assertSame('`i.release_id`', $column);
		$this->assertSame([1, 2, 3], $values);
	}

	public function testScalarReleaseIdFiltersByEquality(): void
	{
		$query = $this->buildQuery(5);

		$this->assertSame([], $query->whereInCalls);
		$this->assertContains('`i.release_id` = :relid', $query->whereCalls);
		$this->assertSame(5, $query->bindValues[':relid']);
	}
}
