<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\ReleaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Regression coverage for `ReleaseModel::assertSourceCategoryAccessForBatch()`, the Release-side
 * counterpart of {@see \Akeeba\ARS\UnitTest\Administrator\Model\ItemModelTest} — both close the same
 * H3/H4 findings (batch move/copy authorising only the DESTINATION category, never the one a record
 * is actually being taken FROM). See `security.md`.
 *
 * Unlike `ItemModel`, a Release's category is a direct column (`category_id`) rather than one hop
 * away through a parent table, so the query this method builds is simpler — but the authorisation
 * shape being tested is identical.
 */
#[CoversClass(ReleaseModel::class)]
#[Group('Model')]
class ReleaseModelTest extends TestCase
{
	protected function tearDown(): void
	{
		Factory::reset();

		parent::tearDown();
	}

	private function stubIdentity(array $permissions): void
	{
		$user              = new User(42);
		$user->permissions = $permissions;

		Factory::$application = new class($user) {
			public function __construct(private User $user)
			{
			}

			public function getIdentity()
			{
				return $this->user;
			}
		};
	}

	private function newModel(RecordingDatabase $db): ReleaseModel
	{
		$model = new ReleaseModel([], null);
		$model->setDatabase($db);

		return $model;
	}

	private function invoke(ReleaseModel $model, array $commands, array $pks): void
	{
		$ref = new ReflectionMethod(ReleaseModel::class, 'assertSourceCategoryAccessForBatch');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$ref->invoke($model, $commands, $pks);
	}

	public function testNoExceptionAndNoQueryWhenTheBatchCommandIsNotAMoveOrCopy(): void
	{
		// batch_copymove for ReleaseModel is 'category_id'; a batch of only, say, a tag change never
		// sets it, so there is nothing to re-check.
		$this->stubIdentity([]);

		$db = new RecordingDatabase();

		$this->invoke($this->newModel($db), ['tag' => 5], [1, 2, 3]);

		$this->assertSame([], $db->queries, 'No permission check should have run at all, so no query should have been issued.');
	}

	public function testNoExceptionWhenThePksListIsEmpty(): void
	{
		$this->stubIdentity([]);

		$db = new RecordingDatabase();

		$this->invoke($this->newModel($db), ['category_id' => 7], []);

		$this->assertSame([], $db->queries);
	}

	public function testAllowsTheMoveWhenTheUserMayEditTheSourceCategory(): void
	{
		$this->stubIdentity(['com_ars.category.3|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		$this->invoke($this->newModel($db), ['category_id' => 99], [5]);

		$this->assertNotEmpty($db->queries, 'The source category should still have been looked up.');
	}

	public function testRejectsTheMoveWhenTheUserMayNotEditTheSourceCategory(): void
	{
		// The user has rights on the TARGET category (99) but not on the SOURCE one (3) the release is
		// actually in — this is exactly the H3/H4 attack shape.
		$this->stubIdentity(['com_ars.category.99|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT');

		$this->invoke($this->newModel($db), ['category_id' => 99], [5]);
	}

	public function testRejectsACopyJustAsReadilyAsAMove(): void
	{
		$this->stubIdentity([]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		$this->expectException(RuntimeException::class);

		$this->invoke($this->newModel($db), ['category_id' => 99, 'move_copy' => 'c'], [5]);
	}

	public function testAReleaseMissingFromTheQueryResultIsSkippedRatherThanRejected(): void
	{
		$this->stubIdentity([]);

		$db         = new RecordingDatabase();
		$db->result = []; // Empty: pk 5 matches no row.

		$this->invoke($this->newModel($db), ['category_id' => 99], [5]);

		$this->addToAssertionCount(1); // Reaching here without an exception IS the assertion.
	}

	public function testEveryPkInABatchIsCheckedNotJustTheFirst(): void
	{
		$this->stubIdentity(['com_ars.category.3|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [
			['id' => 5, 'category_id' => 3],
			['id' => 6, 'category_id' => 4],
		];

		$this->expectException(RuntimeException::class);

		$this->invoke($this->newModel($db), ['category_id' => 99], [5, 6]);
	}
}
