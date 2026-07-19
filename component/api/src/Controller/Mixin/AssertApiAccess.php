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
