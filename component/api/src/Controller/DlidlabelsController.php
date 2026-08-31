<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\AssertApiAccess;
use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\MVC\Controller\ApiController;

/**
 * JSON:API controller for the Download ID labels.
 *
 * A Download ID is a bearer credential: whoever knows the string can download everything the owning user is
 * subscribed to. The endpoint is therefore split in two:
 *
 * - Anyone authenticated may list and read their OWN records, mirroring the frontend Download IDs view.
 * - Seeing other people's records additionally requires core.manage, mirroring the back-end view.
 *
 * The list model is the Administrator one, which does not restrict itself to the current user outside the site
 * application, so the restriction has to be imposed here — see forceOwnershipFilter().
 *
 * Writes follow the back-end DlidlabelController: core.admin may touch anybody's records, everyone else only
 * their own, and never the primary (main) Download ID.
 *
 * @since  7.5.1
 */
class DlidlabelsController extends ApiController
{
	use PopulateModelState;
	use AssertApiAccess;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $contentType = 'dlidlabels';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.5.1
	 */
	protected $default_view = 'dlidlabels';

	public function displayList()
	{
		$this->assertNotGuest();

		$stateMapper = [
			['search', 'filter.search', 'string'],
			['id', 'filter.id', 'int'],
			['user_id', 'filter.user_id', 'int'],
			['dlid', 'filter.dlid', 'string'],
			['title', 'filter.title', 'string'],
			['username', 'filter.username', 'string'],
			['name', 'filter.name', 'string'],
			['email', 'filter.email', 'string'],
			['primary', 'filter.primary', 'int'],
			['published', 'filter.published', 'int'],
		];

		$this->populateListModelState($stateMapper);
		$this->forceOwnershipFilter();

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$this->assertNotGuest();

		if ($id === null) {
			$id = $this->input->get('id', 0, 'int');
		}

		$this->assertCanReadRecord((int) $id);

		return parent::displayItem($id);
	}

	public function delete($id = null)
	{
		$this->assertNotGuest();

		if ($id === null) {
			$id = $this->input->get('id', 0, 'int');
		}

		$user   = $this->app->getIdentity();
		$record = $this->getRecord((int) $id);

		// Deleting one's own Download ID is always allowed, exactly as it is in DlidlabelModel::canDelete().
		if ($record && $record->user_id == $user->id) {
			return parent::delete($id);
		}

		$this->assertCanDelete();

		return parent::delete($id);
	}

	protected function allowAdd($data = [])
	{
		$user = $this->app->getIdentity();

		if (!$user->id || $user->guest) {
			return false;
		}

		if (empty($data)) {
			$data = $this->getRequestData();
		}

		$userId = (int) ($data['user_id'] ?? 0);

		// Only a component administrator may create a Download ID on behalf of somebody else.
		if ($userId && $userId != $user->id) {
			return $user->authorise('core.admin', 'com_ars');
		}

		return true;
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$user     = $this->app->getIdentity();
		$recordId = (int) ($data[$key] ?? 0);

		if (!$recordId) {
			return false;
		}

		if ($user->authorise('core.admin', 'com_ars')) {
			return true;
		}

		if (!$user->id || $user->guest) {
			return false;
		}

		$record = $this->getRecord($recordId);

		if (!$record || $record->user_id != $user->id) {
			return false;
		}

		/**
		 * The primary (main) Download ID is not editable by its owner; its title is fixed and it can never be
		 * unpublished. This mirrors COM_ARS_DLIDLABELS_ERR_CANTEDITDEFAULT in the back-end controller.
		 */
		if ($record->primary) {
			return false;
		}

		/**
		 * A non-administrator may not reassign the record to a different user.
		 *
		 * Joomla's ApiController::edit() calls us with the record ID alone, so the submitted user_id has to be
		 * read back out of the request body here — otherwise a PATCH could hand the record to another account.
		 */
		$requestData = $this->getRequestData();
		$newUserId   = (int) ($data['user_id'] ?? $requestData['user_id'] ?? $record->user_id);

		return $newUserId == $user->id;
	}

	/**
	 * Restrict the list to the current user's own records unless they may manage the component.
	 *
	 * @return  void
	 * @since   7.5.1
	 */
	private function forceOwnershipFilter(): void
	{
		$user = $this->app->getIdentity();

		if ($user->authorise('core.manage', 'com_ars')) {
			return;
		}

		$this->modelState->set('filter.user_id', (int) $user->id);
	}

	/**
	 * Ensure the current user may read the given record.
	 *
	 * A record which does not exist and a record belonging to somebody else are refused identically, so that the
	 * response cannot be used to enumerate which record IDs exist.
	 *
	 * @param   int  $recordId  The Download ID label record to read.
	 *
	 * @return  void
	 * @throws  NotAllowed  When the user may not read the record.
	 * @since   7.5.1
	 */
	private function assertCanReadRecord(int $recordId): void
	{
		$user = $this->app->getIdentity();

		if ($user->authorise('core.manage', 'com_ars')) {
			return;
		}

		$record = $this->getRecord($recordId);

		if (!$record || $record->user_id != $user->id) {
			throw new NotAllowed('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);
		}
	}

	/**
	 * Load a Download ID label record.
	 *
	 * @param   int  $recordId  The record ID.
	 *
	 * @return  object|null  The record, NULL if it does not exist.
	 * @since   7.5.1
	 */
	private function getRecord(int $recordId): ?object
	{
		if (!$recordId) {
			return null;
		}

		$record = $this->getModel('Dlidlabel')->getItem($recordId);

		return (is_object($record) && ($record->id ?? 0)) ? $record : null;
	}
}
