<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\ARS\UnitTest\Stubs\RecordingDatabase;
use Akeeba\Component\ARS\Administrator\Mixin\ModelCategoryDeleteTrait;
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
 * An explicit Deny of core.delete on a category always wins over an Allow on the component (gh-256).
 *
 * The back-end models used to accept core.delete on the record's category OR on the component, so a group denied
 * delete on one category but allowed it on the component could still delete that category's records. They now check
 * the category asset only, which inherits from the component, exactly like the JSON:API's canDeleteIn().
 *
 * ItemModel is covered by the trait tests only: its canDelete() loads the parent release through the MVC factory,
 * which the unit stubs don't provide.
 *
 * The test User stub doesn't model inheritance: a missing asset|action key is a Deny. So "Deny on the category,
 * Allow on the component" is the category key false and the component key true, and "Allow on the category" is the
 * category key true, as Joomla resolves an inherited Allow.
 */
#[CoversClass(CategoryModel::class)]
#[CoversClass(ReleaseModel::class)]
#[CoversClass(AutodescriptionModel::class)]
#[CoversClass(UpdatestreamModel::class)]
#[CoversTrait(ModelCategoryDeleteTrait::class)]
#[Group('Model')]
class CategoryDeletePermissionTest extends TestCase
{
	private const DENIED_ON_CATEGORY = [
		'com_ars|core.delete'            => true,
		'com_ars.category.5|core.delete' => false,
	];

	private const ALLOWED_ON_CATEGORY = [
		'com_ars.category.5|core.delete' => true,
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
	 * Every model, with a record in category 5 and no children, so that permission alone decides.
	 *
	 * @return array<string, array{class-string, object}>
	 */
	public static function provideModels(): array
	{
		return [
			'category'        => [CategoryModel::class, (object) ['id' => 5]],
			'release'         => [ReleaseModel::class, (object) ['id' => 10, 'category_id' => 5]],
			'autodescription' => [AutodescriptionModel::class, (object) ['id' => 10, 'category' => 5]],
			'updatestream'    => [UpdatestreamModel::class, (object) ['id' => 10, 'category' => 5]],
		];
	}

	private function canDelete(string $class, object $record): bool
	{
		$model = new $class([], null);
		$db    = new RecordingDatabase();

		// "How many children does this record have?" — none, so that the permission check alone decides.
		$db->result = 0;
		$model->setDatabase($db);

		$ref = new ReflectionMethod($class, 'canDelete');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return $ref->invoke($model, $record);
	}

	#[DataProvider('provideModels')]
	public function testDenyOnTheCategoryWinsOverAllowOnTheComponent(string $class, object $record): void
	{
		$this->stubIdentity(self::DENIED_ON_CATEGORY);

		$this->assertFalse($this->canDelete($class, $record));
	}

	#[DataProvider('provideModels')]
	public function testAllowOnTheCategoryIsEnough(string $class, object $record): void
	{
		$this->stubIdentity(self::ALLOWED_ON_CATEGORY);

		$this->assertTrue($this->canDelete($class, $record));
	}

	#[DataProvider('provideModels')]
	public function testAllowOnAnotherCategoryIsNotEnough(string $class, object $record): void
	{
		$this->stubIdentity(['com_ars.category.6|core.delete' => true]);

		$this->assertFalse($this->canDelete($class, $record));
	}

	#[DataProvider('provideModels')]
	public function testRecordWithoutAnIdIsNeverDeleted(string $class, object $record): void
	{
		$this->stubIdentity(self::ALLOWED_ON_CATEGORY + ['com_ars|core.delete' => true]);

		$record     = clone $record;
		$record->id = 0;

		$this->assertFalse($this->canDelete($class, $record));
	}

	// -----------------------------------------------------------------------------------------------------------
	// ModelCategoryDeleteTrait
	// -----------------------------------------------------------------------------------------------------------

	private function traitUser(): object
	{
		return new class {
			use ModelCategoryDeleteTrait;

			public function callCanDeleteInCategory(?int $categoryId): bool
			{
				return $this->canDeleteInCategory($categoryId);
			}
		};
	}

	public function testTraitDenyOnTheCategoryWins(): void
	{
		$this->stubIdentity(self::DENIED_ON_CATEGORY);

		$this->assertFalse($this->traitUser()->callCanDeleteInCategory(5));
	}

	public function testTraitChecksTheComponentWithoutACategory(): void
	{
		$this->stubIdentity(['com_ars|core.delete' => true]);

		$this->assertTrue($this->traitUser()->callCanDeleteInCategory(null));
		$this->assertTrue($this->traitUser()->callCanDeleteInCategory(0));
	}

	public function testTraitAllowOnTheCategoryIsEnoughInThatCategoryOnly(): void
	{
		$this->stubIdentity(self::ALLOWED_ON_CATEGORY);

		$this->assertTrue($this->traitUser()->callCanDeleteInCategory(5));
		$this->assertFalse($this->traitUser()->callCanDeleteInCategory(6));
		$this->assertFalse($this->traitUser()->callCanDeleteInCategory(null));
	}
}
