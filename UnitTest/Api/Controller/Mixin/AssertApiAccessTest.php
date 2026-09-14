<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Api\Controller\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Api\Controller\Mixin\AssertApiAccess;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the H5 fix: a JSON:API PATCH could reparent a Release/Item/Autodescription/
 * Updatestream into a category the user has no `core.edit` grant on, because `allowEdit()` is only
 * ever called with the record's primary key — never the submitted body — so it can only authorise the
 * record's EXISTING category, not whatever the request is asking to move it INTO. See `security.md`.
 *
 * `assertCanEditIntoCategory()` and `resolveSubmittedFieldValue()` are the two pieces added to close
 * that gap; both are exercised directly against a test double that `use`s the trait, mirroring the
 * pattern in {@see \Akeeba\ARS\UnitTest\Administrator\Mixin\ControllerReturnURLTraitTest}.
 */
#[CoversClass(AssertApiAccess::class)]
#[Group('Mixin')]
class AssertApiAccessTest extends TestCase
{
	private function controllerWithPermissions(array $permissions, ?array $requestData = null): object
	{
		$user              = new User(42);
		$user->permissions = $permissions;

		$app = new class($user) {
			public function __construct(private User $user)
			{
			}

			public function getIdentity()
			{
				return $this->user;
			}
		};

		return new class($app, $requestData) {
			use AssertApiAccess;

			public $app;

			public $input;

			public function __construct($app, ?array $requestData)
			{
				$this->app = $app;

				if ($requestData !== null)
				{
					$this->input = new class($requestData) {
						public $json;

						public function __construct(array $data)
						{
							$this->json = new class($data) {
								public function __construct(private array $data)
								{
								}

								public function getRaw()
								{
									return json_encode($this->data);
								}
							};
						}

						public function get($name, $default = null, $filter = 'cmd')
						{
							// Mirrors real Joomla behaviour when the request carries no top-level "data"
							// query/POST field of its own: the caller-supplied $default (built from the
							// decoded JSON body) passes straight through.
							return $default;
						}
					};
				}
			}

			public function callAssertCanEditIntoCategory(?int $categoryId): void
			{
				$this->assertCanEditIntoCategory($categoryId);
			}

			public function callResolveSubmittedFieldValue(array $submittedData, array $fieldNames): ?int
			{
				return $this->resolveSubmittedFieldValue($submittedData, $fieldNames);
			}

			public function callGetRequestData(): array
			{
				return $this->getRequestData();
			}
		};
	}

	// -----------------------------------------------------------------------------------------------------------
	// assertCanEditIntoCategory()
	// -----------------------------------------------------------------------------------------------------------

	public function testNullCategoryIdMeansNothingToRecheckAndPasses(): void
	{
		// No permissions granted anywhere; if the check ran at all for a null category, it would throw.
		$controller = $this->controllerWithPermissions([]);

		$controller->callAssertCanEditIntoCategory(null);

		$this->addToAssertionCount(1);
	}

	public function testZeroCategoryIdIsRejectedOutright(): void
	{
		$controller = $this->controllerWithPermissions(['com_ars.category.0|core.edit' => true]);

		$this->expectException(NotAllowed::class);

		$controller->callAssertCanEditIntoCategory(0);
	}

	public function testAuthorisedTargetCategoryPasses(): void
	{
		$controller = $this->controllerWithPermissions(['com_ars.category.5|core.edit' => true]);

		$controller->callAssertCanEditIntoCategory(5);

		$this->addToAssertionCount(1);
	}

	public function testUnauthorisedTargetCategoryIsRejected(): void
	{
		// The user has rights on some OTHER category, just not the one being reparented into.
		$controller = $this->controllerWithPermissions(['com_ars.category.99|core.edit' => true]);

		$this->expectException(NotAllowed::class);

		$controller->callAssertCanEditIntoCategory(5);
	}

	// -----------------------------------------------------------------------------------------------------------
	// resolveSubmittedFieldValue()
	// -----------------------------------------------------------------------------------------------------------

	public function testReturnsNullWhenNoneOfTheFieldNamesArePresent(): void
	{
		$controller = $this->controllerWithPermissions([]);

		$this->assertNull($controller->callResolveSubmittedFieldValue(['title' => 'x'], ['category', 'category_id', 'catid']));
	}

	public function testReturnsTheFirstPresentFieldNameInOrder(): void
	{
		$controller = $this->controllerWithPermissions([]);

		$data = ['catid' => '7', 'category_id' => '9'];

		$this->assertSame(9, $controller->callResolveSubmittedFieldValue($data, ['category', 'category_id', 'catid']));
	}

	public function testCastsTheResolvedValueToInt(): void
	{
		$controller = $this->controllerWithPermissions([]);

		$this->assertSame(5, $controller->callResolveSubmittedFieldValue(['category_id' => '5'], ['category_id']));
	}

	/**
	 * A submitted `category_id: 0` (or any falsy-but-present value) must still be recognised as
	 * PRESENT — distinct from the field being absent altogether — because `assertCanEditIntoCategory()`
	 * relies on that distinction: NULL means "the request never touched the category", 0 means "the
	 * request explicitly asked for an invalid one" and must still be rejected.
	 */
	public function testAnExplicitZeroValueIsStillTreatedAsPresent(): void
	{
		$controller = $this->controllerWithPermissions([]);

		$this->assertSame(0, $controller->callResolveSubmittedFieldValue(['category_id' => 0], ['category_id']));
	}

	public static function fieldNameFallbackProvider(): array
	{
		return [
			'only "category" present'     => [['category' => '3'], 3],
			'only "category_id" present'  => [['category_id' => '4'], 4],
			'only "catid" present'        => [['catid' => '5'], 5],
		];
	}

	#[DataProvider('fieldNameFallbackProvider')]
	public function testFallsThroughTheFieldNameListInOrder(array $data, int $expected): void
	{
		$controller = $this->controllerWithPermissions([]);

		$this->assertSame($expected, $controller->callResolveSubmittedFieldValue($data, ['category', 'category_id', 'catid']));
	}

	// -----------------------------------------------------------------------------------------------------------
	// The end-to-end shape of the H5 attack: a PATCH naming a target category the editor cannot reach
	// -----------------------------------------------------------------------------------------------------------

	public function testTheH5AttackShapeIsRejectedEndToEnd(): void
	{
		// The attacker has core.edit on category 1 (where the record currently lives) but NOT on
		// category 2 (where the PATCH is trying to move it).
		$controller = $this->controllerWithPermissions(['com_ars.category.1|core.edit' => true]);

		$submitted  = ['category_id' => 2];
		$categoryId = $controller->callResolveSubmittedFieldValue($submitted, ['category_id']);

		$this->expectException(NotAllowed::class);

		$controller->callAssertCanEditIntoCategory($categoryId);
	}

	public function testAPatchThatNeverTouchesTheCategoryFieldIsNotRechecked(): void
	{
		// No permissions anywhere — if resolveSubmittedFieldValue() ever fell back to 0 instead of NULL
		// for an absent field, this would incorrectly throw.
		$controller = $this->controllerWithPermissions([]);

		$submitted  = ['title' => 'Renamed, category untouched'];
		$categoryId = $controller->callResolveSubmittedFieldValue($submitted, ['category_id']);

		$controller->callAssertCanEditIntoCategory($categoryId);

		$this->addToAssertionCount(1);
	}

	// -----------------------------------------------------------------------------------------------------------
	// getRequestData()
	// -----------------------------------------------------------------------------------------------------------

	public function testGetRequestDataDecodesTheRawJsonBody(): void
	{
		$controller = $this->controllerWithPermissions([], ['category_id' => 5, 'title' => 'Example']);

		$this->assertSame(['category_id' => 5, 'title' => 'Example'], $controller->callGetRequestData());
	}
}
