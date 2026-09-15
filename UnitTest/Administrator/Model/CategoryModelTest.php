<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\CategoryModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Regression coverage for the M5 fix: `CategoryModel` had a `canDelete()` override that correctly
 * checks the category's own asset (`com_ars.category.<id>`) before falling back to the component root,
 * but no equivalent `canEditState()` override — bulk publish/unpublish/archive/trash fell back to stock
 * `AdminModel::canEditState()`, which only checks `core.edit.state` at the component root. A group denied
 * `core.edit.state` on a specific category but granted it at component level could still bulk-change that
 * category's state. See `security.md`.
 */
#[CoversClass(CategoryModel::class)]
#[Group('Model')]
class CategoryModelTest extends TestCase
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

	private function invoke(object $record): bool
	{
		$model = new CategoryModel([], null);

		$ref = new ReflectionMethod(CategoryModel::class, 'canEditState');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$ref->setAccessible(true);
		}

		return (bool) $ref->invoke($model, $record);
	}

	public function testAllowedWhenTheUserHasEditStateOnTheCategoryItself(): void
	{
		$this->stubIdentity(['com_ars.category.5|core.edit.state' => true]);

		$this->assertTrue($this->invoke((object) ['id' => 5]));
	}

	public function testAllowedWhenTheUserHasComponentWideEditStateEvenWithoutCategoryGrant(): void
	{
		$this->stubIdentity(['com_ars|core.edit.state' => true]);

		$this->assertTrue($this->invoke((object) ['id' => 5]));
	}

	/**
	 * The regression case itself: a user with NO grant on this specific category, and none at the
	 * component root either, must be rejected — matching `canDelete()`'s existing pattern, and unlike
	 * stock `AdminModel::canEditState()`, which never consulted the category asset at all.
	 */
	public function testRejectedWhenTheUserHasNeitherCategoryNorComponentEditState(): void
	{
		// Grants on a DIFFERENT category must not leak into this one.
		$this->stubIdentity(['com_ars.category.99|core.edit.state' => true]);

		$this->assertFalse($this->invoke((object) ['id' => 5]));
	}

	public function testRejectedWithNoPermissionsAtAll(): void
	{
		$this->stubIdentity([]);

		$this->assertFalse($this->invoke((object) ['id' => 5]));
	}
}
