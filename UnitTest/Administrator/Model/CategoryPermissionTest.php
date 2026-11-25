<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Mixin\ModelCategoryPermissionTrait;
use Akeeba\Component\ARS\Administrator\Model\AutodescriptionModel;
use Akeeba\Component\ARS\Administrator\Model\CategoryModel;
use Akeeba\Component\ARS\Administrator\Model\ReleaseModel;
use Akeeba\Component\ARS\Administrator\Model\UpdatestreamModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * An explicit Deny on a category always wins over an Allow on the component, for deleting (canDelete(), core.delete)
 * and for publishing / unpublishing (canEditState(), core.edit.state) a record in that category (gh-256).
 *
 * The back-end models used to accept the privilege on the record's category OR on the component, so a group denied it
 * on one category but allowed it on the component could still delete, publish or unpublish that category's records.
 * Auto descriptions and update streams checked the component only. All of them now check the category asset only,
 * which inherits from the component, exactly like the JSON:API's canDeleteIn().
 *
 * ItemModel is covered by the trait tests only: it loads the parent release through the MVC factory, which the unit
 * stubs don't provide.
 *
 * The test User stub doesn't model inheritance: a missing asset|action key is a Deny. So "Deny on the category,
 * Allow on the component" is the category key false and the component key true, and "Allow on the category" is the
 * category key true, as Joomla resolves an inherited Allow.
 */
#[CoversClass(CategoryModel::class)]
#[CoversClass(ReleaseModel::class)]
#[CoversClass(AutodescriptionModel::class)]
#[CoversClass(UpdatestreamModel::class)]
#[CoversTrait(ModelCategoryPermissionTrait::class)]
#[Group('Model')]
class CategoryPermissionTest extends TestCase
{
	/** The model method which checks each privilege. */
	private const METHODS = [
		'core.delete'     => 'canDelete',
		'core.edit.state' => 'canEditState',
	];

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

	/**
	 * Every model and privilege, with a record in category 5 and no children, so that permission alone decides.
	 *
	 * @return array<string, array{string, class-string, object}>
	 */
	public static function provideModels(): array
	{
		$records = [
			'category'        => [CategoryModel::class, (object) ['id' => 5]],
			'release'         => [ReleaseModel::class, (object) ['id' => 10, 'category_id' => 5]],
			'autodescription' => [AutodescriptionModel::class, (object) ['id' => 10, 'category' => 5]],
			'updatestream'    => [UpdatestreamModel::class, (object) ['id' => 10, 'category' => 5]],
		];
		$cases   = [];

		foreach (array_keys(self::METHODS) as $action)
		{
			foreach ($records as $name => [$class, $record])
			{
				$cases[$name . ' ' . $action] = [$action, $class, $record];
			}
		}

		return $cases;
	}

	private function check(string $action, string $class, object $record): bool
	{
		$model = new $class([], null);
		$db    = new RecordingDatabase();

		// "How many children does this record have?" — none, so that the permission check alone decides.
		$db->result = 0;
		$model->setDatabase($db);

		$ref = new ReflectionMethod($class, self::METHODS[$action]);

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return (bool) $ref->invoke($model, $record);
	}

	#[DataProvider('provideModels')]
	public function testDenyOnTheCategoryWinsOverAllowOnTheComponent(string $action, string $class, object $record): void
	{
		$this->stubIdentity(['com_ars|' . $action => true, 'com_ars.category.5|' . $action => false]);

		$this->assertFalse($this->check($action, $class, $record));
	}

	#[DataProvider('provideModels')]
	public function testAllowOnTheCategoryIsEnough(string $action, string $class, object $record): void
	{
		$this->stubIdentity(['com_ars.category.5|' . $action => true]);

		$this->assertTrue($this->check($action, $class, $record));
	}

	#[DataProvider('provideModels')]
	public function testAllowOnAnotherCategoryIsNotEnough(string $action, string $class, object $record): void
	{
		$this->stubIdentity(['com_ars.category.6|' . $action => true]);

		$this->assertFalse($this->check($action, $class, $record));
	}

	public static function provideDeleteModels(): array
	{
		return array_filter(
			self::provideModels(), fn(array $case): bool => $case[0] === 'core.delete'
		);
	}

	#[DataProvider('provideDeleteModels')]
	public function testRecordWithoutAnIdIsNeverDeleted(string $action, string $class, object $record): void
	{
		$this->stubIdentity(['com_ars.category.5|core.delete' => true, 'com_ars|core.delete' => true]);

		$record     = clone $record;
		$record->id = 0;

		$this->assertFalse($this->check($action, $class, $record));
	}

	// -----------------------------------------------------------------------------------------------------------
	// ModelCategoryPermissionTrait
	// -----------------------------------------------------------------------------------------------------------

	private function traitUser(): object
	{
		return new class {
			use ModelCategoryPermissionTrait;

			public function call(string $action, ?int $categoryId): bool
			{
				return $action === 'core.delete'
					? $this->canDeleteInCategory($categoryId)
					: $this->canEditStateInCategory($categoryId);
			}
		};
	}

	public static function provideActions(): array
	{
		return array_map(fn(string $action): array => [$action], array_combine(array_keys(self::METHODS), array_keys(self::METHODS)));
	}

	#[DataProvider('provideActions')]
	public function testTraitDenyOnTheCategoryWins(string $action): void
	{
		$this->stubIdentity(['com_ars|' . $action => true, 'com_ars.category.5|' . $action => false]);

		$this->assertFalse($this->traitUser()->call($action, 5));
	}

	#[DataProvider('provideActions')]
	public function testTraitChecksTheComponentWithoutACategory(string $action): void
	{
		$this->stubIdentity(['com_ars|' . $action => true]);

		$this->assertTrue($this->traitUser()->call($action, null));
		$this->assertTrue($this->traitUser()->call($action, 0));
	}

	#[DataProvider('provideActions')]
	public function testTraitAllowOnTheCategoryIsEnoughInThatCategoryOnly(string $action): void
	{
		$this->stubIdentity(['com_ars.category.5|' . $action => true]);

		$this->assertTrue($this->traitUser()->call($action, 5));
		$this->assertFalse($this->traitUser()->call($action, 6));
		$this->assertFalse($this->traitUser()->call($action, null));
	}

	#[DataProvider('provideActions')]
	public function testTraitChecksOnlyTheRequestedPrivilege(string $action): void
	{
		$other = $action === 'core.delete' ? 'core.edit.state' : 'core.delete';

		$this->stubIdentity(['com_ars.category.5|' . $other => true, 'com_ars|' . $other => true]);

		$this->assertFalse($this->traitUser()->call($action, 5));
	}
}
