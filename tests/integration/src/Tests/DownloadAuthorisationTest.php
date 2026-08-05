<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * The download controller checks access at item, release AND category level
 * (`ItemController::download()` via `ControllerCRIAccessTrait::accessControlFor()`). This class walks
 * the actor x item matrix and, for every cell, proves either that the bytes came back or that they
 * provably did not.
 *
 * The download URL takes the item id as `item_id`, not `id` — confirmed empirically: `id=` on this
 * route 500s (`ItemController::download()` reads `$this->input->getInt('item_id', null)`, so `id=`
 * leaves it null and `preDownloadCheck()`/`accessControlItem(0, ...)` blow up on a category/item that
 * cannot be found). The task brief describes the URL with `id=`; that is wrong, and every request in
 * this class uses `item_id=` instead.
 *
 * Every expected outcome below was produced by actually running the request against the provisioned
 * site (see the class docblock convention in HarnessTest and tests/integration/README.md) — not
 * inferred from reading the controller.
 *
 * @since 7.5.0
 */
#[Group('download')]
#[Group('authorisation')]
class DownloadAuthorisationTest extends AbstractE2ETestCase
{
	/**
	 * The actor x item authorisation matrix, established empirically against the live site:
	 *
	 *   - publicFile: open to everyone, including a guest.
	 *   - publicSubscriberItem / subscriberOnlyReleaseFile / restrictedFile: gated on the ARS
	 *     Subscribers view level, so guest and client are refused, subscriber and manager get it.
	 *   - publicUnpublishedItem / unpublishedReleaseFile / unpublishedCatFile: unpublished at item,
	 *     release or category level respectively, so `ControllerCRIAccessTrait::accessControlFor()`
	 *     404s everyone, including the manager.
	 *   - secretFile: gated on the ARS Secret view level, which no test account — not even the
	 *     manager, who is deliberately not a Super User — holds. Refused for everyone.
	 *
	 * @return  iterable
	 * @since   7.5.0
	 */
	public static function authorisationMatrixProvider(): iterable
	{
		// item key => [guest, client, subscriber, manager] as "is this actor allowed?"
		$matrix = [
			'publicFile'                => [true, true, true, true],
			'publicSubscriberItem'      => [false, false, true, true],
			'publicUnpublishedItem'     => [false, false, false, false],
			'subscriberOnlyReleaseFile' => [false, false, true, true],
			'unpublishedReleaseFile'    => [false, false, false, false],
			'restrictedFile'            => [false, false, true, true],
			'secretFile'                => [false, false, false, false],
			'unpublishedCatFile'        => [false, false, false, false],
		];

		$actors = ['guest', 'client', 'subscriber', 'manager'];

		foreach ($matrix as $itemKey => $allowedByActor)
		{
			foreach ($actors as $index => $actor)
			{
				yield sprintf('%s as %s', $itemKey, $actor) => [$actor, $itemKey, $allowedByActor[$index]];
			}
		}
	}

	/**
	 * Every cell of the actor x item authorisation matrix resolves the way the live site resolves it.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	#[DataProvider('authorisationMatrixProvider')]
	public function testDownloadAuthorisationMatrix(string $actor, string $itemKey, bool $expectAllowed): void
	{
		$surfer   = $this->actorSurfer($actor);
		$itemId   = static::$fixtures->itemId($itemKey);
		$sentinel = static::$fixtures->file($itemKey)['sentinel'];

		$response = $surfer->get($this->siteUrl([
			'view'   => 'item',
			'task'   => 'download',
			'format' => 'raw',
			'item_id' => $itemId,
		]));

		if ($expectAllowed)
		{
			$this->assertStatus(
				200,
				$response,
				sprintf('%s could not download "%s", though this actor should be allowed to.', ucfirst($actor), $itemKey)
			);
			$this->assertBodyContains(
				$sentinel,
				$response,
				sprintf('%s downloaded "%s" but did not receive its actual bytes.', ucfirst($actor), $itemKey)
			);

			return;
		}

		$this->assertRefused(
			$surfer,
			$response,
			sprintf('%s was not refused "%s".', ucfirst($actor), $itemKey)
		);
		$this->assertBodyNotContains(
			$sentinel,
			$response,
			sprintf('%s was refused "%s", but the file contents came back anyway.', ucfirst($actor), $itemKey)
		);
	}

	/**
	 * Nobody holds the ARS Secret view level, so nobody — not even while authenticated — can reach the
	 * secret file. This restates one row of the matrix above explicitly, because it is the row the
	 * whole "ARS Secret" fixture level exists to prove.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testNobodyHoldsTheSecretViewLevelSoNobodyCanDownloadTheSecretFile(): void
	{
		$itemId   = static::$fixtures->itemId('secretFile');
		$sentinel = static::$fixtures->file('secretFile')['sentinel'];

		foreach (['guest', 'client', 'subscriber', 'manager'] as $actor)
		{
			$surfer   = $this->actorSurfer($actor);
			$response = $surfer->get($this->siteUrl([
				'view'    => 'item',
				'task'    => 'download',
				'format'  => 'raw',
				'item_id' => $itemId,
			]));

			$this->assertRefused($surfer, $response, sprintf('%s downloaded the secret file.', ucfirst($actor)));
			$this->assertBodyNotContains(
				$sentinel,
				$response,
				sprintf('%s was refused the secret file, but its contents came back anyway.', ucfirst($actor))
			);
		}
	}

	/**
	 * The manager account holds full `core.manage` on com_ars but is deliberately not a Super User
	 * (see tests/integration/README.md), so it must not be granted the Secret view level either. A
	 * component-management privilege must not double as a view-level bypass.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testAManagerWhoIsNotASuperUserIsRefusedTheSecretFile(): void
	{
		$manager  = $this->loggedIn('manager');
		$itemId   = static::$fixtures->itemId('secretFile');
		$sentinel = static::$fixtures->file('secretFile')['sentinel'];

		$response = $manager->get($this->siteUrl([
			'view'    => 'item',
			'task'    => 'download',
			'format'  => 'raw',
			'item_id' => $itemId,
		]));

		$this->assertRefused($manager, $response, 'A manager who is not a Super User downloaded the secret file.');
		$this->assertBodyNotContains(
			$sentinel,
			$response,
			'A manager who is not a Super User was refused the secret file, but its contents came back anyway.'
		);
	}

	/**
	 * Resolve a named actor to a surfer.
	 *
	 * @param   string  $actor  'guest', 'client', 'subscriber' or 'manager'.
	 *
	 * @return  Surfer
	 * @since   7.5.0
	 */
	private function actorSurfer(string $actor): Surfer
	{
		return $actor === 'guest' ? $this->guest() : $this->loggedIn($actor);
	}
}
