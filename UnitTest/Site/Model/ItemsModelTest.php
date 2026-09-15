<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Site\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Regression coverage for the M4 fix: the frontend item-picker modal used by the `plg_editors-xtd_arslink`
 * editor button queried {@see \Akeeba\Component\ARS\Administrator\Model\ItemsModel::getListQuery()} —
 * shared with the normal frontend browse — which filters only the item's own `published`/`access`
 * columns. Unlike the normal browse (which always pre-authorises one specific release/category via
 * `accessControlRelease()`/`accessControlCategory()` before this query ever runs), the modal has no such
 * gate and queries across every release/category, so it would list items belonging to unpublished or
 * access-restricted releases/categories. See `security.md`.
 *
 * `Site\Model\ItemsModel::getListQuery()` closes that gap by adding `r.published`/`c.published`/
 * `r.access`/`c.access` conditions on top of whatever the parent query already built. These tests use
 * {@see RecordingDatabase} to assert the QUERY SHAPE, exactly like the existing
 * {@see \Akeeba\ARS\UnitTest\Administrator\Model\ItemsModelReleaseFilterTest} does for the parent class.
 */
#[CoversClass(ItemsModel::class)]
#[Group('Model')]
class ItemsModelTest extends TestCase
{
	private function buildQuery($access): object
	{
		$db = new RecordingDatabase();

		$model = new ItemsModel([], null);
		$model->setDatabase($db);
		$model->setState('filter.access', $access);

		$ref = new ReflectionMethod(ItemsModel::class, 'getListQuery');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($model);
	}

	public function testTheReleaseAndCategoryMustBePublished(): void
	{
		$query = $this->buildQuery(null);

		$this->assertContains('`r.published` = 1', $query->whereCalls);
		$this->assertContains('`c.published` = 1', $query->whereCalls);
	}

	public function testAScalarAccessLevelIsAppliedToBothReleaseAndCategory(): void
	{
		$query = $this->buildQuery(3);

		$this->assertContains('`r.access` = :relAccess', $query->whereCalls);
		$this->assertContains('`c.access` = :catAccess', $query->whereCalls);
		$this->assertSame(3, $query->bindValues[':relAccess']);
		$this->assertSame(3, $query->bindValues[':catAccess']);
	}

	/**
	 * The parent (item-level) filter already emits its own `whereIn('i.access', …)` for an array access
	 * filter, so this override's job is specifically to ALSO add one for `r.access` and one for
	 * `c.access` — not to be the only whereIn() call.
	 */
	public function testAnArrayOfAccessLevelsUsesWhereInOnBothReleaseAndCategoryColumns(): void
	{
		$query = $this->buildQuery([1, 3, 5]);

		$columns = array_map(fn($call) => $call[0], $query->whereInCalls);
		$this->assertContains('`r.access`', $columns);
		$this->assertContains('`c.access`', $columns);

		foreach ($query->whereInCalls as $call)
		{
			if (in_array($call[0], ['`r.access`', '`c.access`'], true))
			{
				$this->assertSame([1, 3, 5], $call[1]);
			}
		}
	}

	/**
	 * `getAuthorisedViewLevels()` (the real source of `filter.access` in production) always returns at
	 * least the Public level, so an empty array is not a realistic input — but if it ever happened, this
	 * override must not add its OWN `IN ()`-producing whereIn() call for `r.access`/`c.access` on top of
	 * whatever the parent's (pre-existing, out-of-scope-for-this-fix) item-level filter already does.
	 */
	public function testAnEmptyAccessArrayAddsNoAdditionalWhereInClauseForReleaseOrCategory(): void
	{
		$query = $this->buildQuery([]);

		$columns = array_map(fn($call) => $call[0], $query->whereInCalls);
		$this->assertNotContains('`r.access`', $columns);
		$this->assertNotContains('`c.access`', $columns);
	}

	public function testNoAccessFilterAddsNeitherEqualityNorWhereInConditionsForReleaseOrCategoryAccess(): void
	{
		$query = $this->buildQuery(null);

		$columns = array_map(fn($call) => $call[0], $query->whereInCalls);
		$this->assertNotContains('`r.access`', $columns);
		$this->assertNotContains('`c.access`', $columns);
		$this->assertNotContains('`r.access` = :relAccess', $query->whereCalls);
		$this->assertNotContains('`c.access` = :catAccess', $query->whereCalls);
	}
}
