<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;

/**
 * Authorisation helpers for the ARS API controllers.
 *
 * The API application reuses the Administrator (back-end) models which, by design, do NOT restrict their result set to
 * the calling user's authorised view levels — an administrator is expected to see everything. Combined with the
 * JSON:API views exposing file names, direct URLs and hashes, an authenticated-but-unprivileged API token holder could
 * otherwise enumerate access-restricted downloads and modify records outside their remit. These helpers re-introduce
 * the component's authorisation so the API mirrors the back-end: `core.manage` is required to use the component at all,
 * and create/edit are additionally gated by the per-category permissions.
 *
 * @since  7.5.0
 */
trait AssertApiAccess
{
	/**
	 * Ensure the current user may manage the component, i.e. would be allowed to access it in the back-end.
	 *
	 * @return  void
	 * @throws  NotAllowed  When the user lacks the core.manage privilege.
	 * @since   7.5.0
	 */
	protected function assertCanManage(): void
	{
		if (!$this->app->getIdentity()->authorise('core.manage', 'com_ars'))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);
		}
	}

	/**
	 * Ensure the current user is authenticated, i.e. not a guest.
	 *
	 * The API application authenticates the request before we get here, but a failed or absent authentication
	 * leaves us with the guest user rather than with no user at all. Endpoints which are available to any logged
	 * in user — as opposed to the ones gated behind core.manage — have to say so explicitly.
	 *
	 * @return  void
	 * @throws  NotAllowed  When the request is not authenticated.
	 * @since   7.5.1
	 */
	protected function assertNotGuest(): void
	{
		$user = $this->app->getIdentity();

		if (!$user || $user->guest || !$user->id)
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);
		}
	}

	/**
	 * Ensure the current user may delete a record.
	 *
	 * Joomla's ApiController::delete() only checks core.delete at the component level. Records which live in a
	 * category are additionally subject to that category's permissions, exactly as they are in the back-end.
	 *
	 * @param   int|null  $categoryId  The ID of the category the record belongs to, NULL for component-level records.
	 *
	 * @return  void
	 * @throws  NotAllowed  When the user may not delete the record.
	 * @since   7.5.1
	 */
	protected function assertCanDelete(?int $categoryId = null): void
	{
		$this->assertCanManage();

		$asset = empty($categoryId) ? 'com_ars' : ('com_ars.category.' . $categoryId);

		if (!$this->app->getIdentity()->authorise('core.delete', $asset))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', 403);
		}
	}

	/**
	 * Read the record data submitted with a write (POST/PATCH) request.
	 *
	 * This mirrors the way Joomla's core ApiController::save() reads the submitted data, so that authorisation checks
	 * performed *before* the record is saved evaluate the exact same values that will be persisted.
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	protected function getRequestData(): array
	{
		$raw = json_decode($this->input->json->getRaw(), true);

		return (array) $this->input->get('data', is_array($raw) ? $raw : [], 'array');
	}
}
