<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Mixin;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\ScriptedRecordingDatabase;
use Akeeba\Component\ARS\Administrator\Mixin\ModelCopyTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * generateNewTitle() drives a `while ($rawData = $db->setQuery($query)->loadAssoc() ?: '')` collision-retry loop:
 * as long as the (alias, category) pair still matches an existing row, it increments the title and re-dashes the
 * alias and tries again. `StringHelper::increment()` in the stubs is Joomla's own implementation copied verbatim
 * (see joomla-stubs.php), so the exact incrementing strings asserted here ("My Title (2)", "my-alias-2", ...) are
 * real Joomla behaviour, not invented.
 */
#[CoversClass(ModelCopyTrait::class)]
#[Group('Mixin')]
class ModelCopyTraitTest extends TestCase
{
	private function fakeTable(): object
	{
		return new class {
			public $title = '';

			public $alias = '';

			public $catid;

			public function hasField($field)
			{
				return in_array($field, ['title', 'alias', 'catid'], true);
			}

			public function getColumnAlias($field)
			{
				return $field;
			}

			public function getTableName()
			{
				return '#__ars_fake';
			}

			public function reset()
			{
			}

			public function bind($data)
			{
				foreach ((array) $data as $key => $value)
				{
					$this->$key = $value;
				}
			}
		};
	}

	private function subject(ScriptedRecordingDatabase $db, object $table): object
	{
		return new class($db, $table) {
			use ModelCopyTrait;

			public function __construct(private $db, private $table)
			{
				// Anything other than '_core_categories' takes the ModelCopyTrait implementation instead of
				// delegating to a (non-existent, in this double) parent::generateNewTitle().
				$this->_parent_table = 'FakeCategory';
			}

			public function getTable()
			{
				return $this->table;
			}

			public function getDatabase()
			{
				return $this->db;
			}

			public function callGenerateNewTitle($categoryId, $alias, $title)
			{
				$ref = new ReflectionMethod($this, 'generateNewTitle');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$ref->setAccessible(true);
				}

				return $ref->invoke($this, $categoryId, $alias, $title);
			}
		};
	}

	public function testNoCollisionLeavesTitleAndAliasUnchanged(): void
	{
		$db             = new ScriptedRecordingDatabase();
		$db->assocQueue = [null];

		$model = $this->subject($db, $this->fakeTable());

		[$title, $alias] = $model->callGenerateNewTitle(5, 'my-alias', 'My Title');

		$this->assertSame('My Title', $title);
		$this->assertSame('my-alias', $alias);
	}

	public function testSingleCollisionIncrementsTitleAndAlias(): void
	{
		$db             = new ScriptedRecordingDatabase();
		$db->assocQueue = [
			['title' => 'My Title', 'alias' => 'my-alias'],
			null,
		];

		$model = $this->subject($db, $this->fakeTable());

		[$title, $alias] = $model->callGenerateNewTitle(5, 'my-alias', 'My Title');

		$this->assertSame('My Title (2)', $title);
		$this->assertSame('my-alias-2', $alias);
	}

	public function testRepeatedCollisionsKeepIncrementingUntilFree(): void
	{
		$db             = new ScriptedRecordingDatabase();
		$db->assocQueue = [
			['title' => 'My Title', 'alias' => 'my-alias'],
			['title' => 'My Title (2)', 'alias' => 'my-alias-2'],
			null,
		];

		$model = $this->subject($db, $this->fakeTable());

		[$title, $alias] = $model->callGenerateNewTitle(5, 'my-alias', 'My Title');

		$this->assertSame('My Title (3)', $title);
		$this->assertSame('my-alias-3', $alias);
	}
}
