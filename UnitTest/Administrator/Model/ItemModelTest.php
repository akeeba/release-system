<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Regression coverage for `ItemModel::assertSourceCategoryAccessForBatch()`, added to close the H3/H4
 * findings (batch move/copy authorising only the DESTINATION category, never the one a record is
 * actually being taken FROM) — see `security.md`.
 *
 * Joomla's `AdminModel::batchMove()`/`batchCopy()` overwrite the table's category (and, for copy, the
 * primary key) before `onBeforeBatch()` ever runs, so the fix has to authorise the CURRENT category of
 * every `$pk`, read fresh from the database, before Joomla mutates anything — which is exactly what
 * this method does and exactly what these tests pin down.
 */
#[CoversClass(ItemModel::class)]
#[Group('Model')]
class ItemModelTest extends TestCase
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

	private function newModel(RecordingDatabase $db): ItemModel
	{
		$model = new ItemModel([], null);
		$model->setDatabase($db);

		return $model;
	}

	private function invoke(ItemModel $model, array $commands, array $pks): void
	{
		$ref = new ReflectionMethod(ItemModel::class, 'assertSourceCategoryAccessForBatch');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		$ref->invoke($model, $commands, $pks);
	}

	public function testNoExceptionAndNoQueryWhenTheBatchCommandIsNotAMoveOrCopy(): void
	{
		// batch_copymove for ItemModel is 'release_id'; a batch of, say, only access-level changes never
		// sets it, so there is no category reparenting happening at all and nothing to re-check.
		$this->stubIdentity([]); // No permissions granted anywhere; would throw if the check ran at all.

		$db = new RecordingDatabase();

		$this->invoke($this->newModel($db), ['assetgroup_id' => 5], [1, 2, 3]);

		$this->assertSame([], $db->queries, 'No permission check should have run at all, so no query should have been issued.');
	}

	public function testNoExceptionWhenThePksListIsEmpty(): void
	{
		$this->stubIdentity([]);

		$db = new RecordingDatabase();

		$this->invoke($this->newModel($db), ['release_id' => 7], []);

		$this->assertSame([], $db->queries);
	}

	public function testAllowsTheMoveWhenTheUserMayEditTheSourceCategory(): void
	{
		$this->stubIdentity(['com_ars.category.3|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		// No exception means the check passed.
		$this->invoke($this->newModel($db), ['release_id' => 99], [5]);

		$this->assertNotEmpty($db->queries, 'The source category should still have been looked up.');
	}

	public function testRejectsTheMoveWhenTheUserMayNotEditTheSourceCategory(): void
	{
		// The user has rights on the TARGET release/category (99) but not on the SOURCE one (3) the item
		// is actually in — this is exactly the H3/H4 attack shape.
		$this->stubIdentity(['com_ars.category.99|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT');

		$this->invoke($this->newModel($db), ['release_id' => 99], [5]);
	}

	public function testRejectsACopyJustAsReadilyAsAMove(): void
	{
		// The source-category check applies regardless of the move_copy command, since duplicating a
		// record out of an inaccessible category is exactly as unauthorised as moving it out.
		$this->stubIdentity([]);

		$db         = new RecordingDatabase();
		$db->result = [['id' => 5, 'category_id' => 3]];

		$this->expectException(RuntimeException::class);

		$this->invoke($this->newModel($db), ['release_id' => 99, 'move_copy' => 'c'], [5]);
	}

	public function testAnItemMissingFromTheQueryResultIsSkippedRatherThanRejected(): void
	{
		// A $pk that does not resolve to any row (already deleted, or a race) has category 0, which the
		// method deliberately treats as "nothing to authorise" rather than an implicit denial — Joomla's
		// own row-existence check in batchMove()/batchCopy() is what actually rejects a missing record.
		$this->stubIdentity([]);

		$db         = new RecordingDatabase();
		$db->result = []; // Empty: pk 5 matches no row.

		$this->invoke($this->newModel($db), ['release_id' => 99], [5]);

		$this->addToAssertionCount(1); // Reaching here without an exception IS the assertion.
	}

	public function testEveryPkInABatchIsCheckedNotJustTheFirst(): void
	{
		// pk 5's category (3) is authorised, but pk 6's category (4) is not — the whole batch must be
		// rejected, not silently partially applied.
		$this->stubIdentity(['com_ars.category.3|core.edit' => true]);

		$db         = new RecordingDatabase();
		$db->result = [
			['id' => 5, 'category_id' => 3],
			['id' => 6, 'category_id' => 4],
		];

		$this->expectException(RuntimeException::class);

		$this->invoke($this->newModel($db), ['release_id' => 99], [5, 6]);
	}
}
