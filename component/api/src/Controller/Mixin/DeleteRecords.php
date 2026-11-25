<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\Response\JsonResponse;

/**
 * Deletes a record through the JSON:API, authorised by the controller's own allowDelete() alone.
 *
 * Joomla's ApiController::delete() can't be used. Up to Joomla 5.4.10 and 6.2.0 it requires core.delete on the
 * whole component before it even looks at the record. Joomla 5.4.11+ / 6.2.1+ call allowDelete() per record instead,
 * but its default implementation does the same component-wide check. That refuses what the back-end allows: deleting
 * your own Download ID label, and deleting a release or item where core.delete is granted on its category only. This
 * reproduces the Joomla 6.2.1 delete() on every Joomla version ARS supports, with two deliberate differences:
 *
 * - The model is always the controller's own. Joomla takes its name from the `model` request parameter, which would
 *   let a request authorised for one resource type delete a record of another.
 * - Only one record is deleted per request, as the `v1/ars/<resource>/:id` routes only ever carry one ID.
 *
 * The "Access Web Services" (core.api) permission is not checked here. Joomla's ApiDispatcher checks it for every
 * ARS route before the controller runs, on the Joomla versions which have it.
 *
 * @since  7.5.3
 */
trait DeleteRecords
{
	/**
	 * Removes a record.
	 *
	 * @param   int|null  $id  The record ID; NULL to read it from the request.
	 *
	 * @return  void
	 * @since   7.5.3
	 */
	public function delete($id = null)
	{
		$this->deleteRecord($id);
	}

	/**
	 * Is the current user allowed to delete this record?
	 *
	 * Joomla 5.4.11+ / 6.2.1+ declare this method without parameters but call it with them; Joomla 8 will declare
	 * them. Every implementation must refuse a record which does not exist.
	 *
	 * @param   array   $data  The record's primary key, as [$key => $id].
	 * @param   string  $key   The name of the primary key.
	 *
	 * @return  bool
	 * @since   7.5.3
	 */
	abstract protected function allowDelete($data = [], $key = 'id'): bool;

	/**
	 * Removes a record, after authorising it with allowDelete().
	 *
	 * Answers like Joomla 6.2.1: 204 on success; 404 for no ID; 403 when allowDelete() refuses; 500 when the model
	 * fails with an error; 409 when the model refuses without one; 404 when the model says the record is missing.
	 *
	 * @param   int|null  $id  The record ID; NULL to read it from the request.
	 *
	 * @return  void
	 * @throws  NotAllowed        When allowDelete() refuses the record.
	 * @throws  ResourceNotFound  When no record ID was given.
	 * @throws  \RuntimeException When the model can't be created, or fails with an error.
	 * @since   7.5.3
	 */
	protected function deleteRecord($id = null): void
	{
		// The singular of every ARS content type: categories => category, releases => release, and so on. Joomla's own
		// inflector is a different class on Joomla 5 and 6, so it is not used.
		$modelName = str_ends_with($this->contentType, 'ies')
			? substr($this->contentType, 0, -3) . 'y'
			: substr($this->contentType, 0, -1);

		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel($modelName, '', ['ignore_request' => true]);

		if (!$model)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
		}

		$id = (int) ($id ?? $this->input->getInt('id'));

		if (empty($id))
		{
			throw new ResourceNotFound(Text::_('JLIB_APPLICATION_ERROR_RECORD'), 404);
		}

		$key = $model->getTable()->getKeyName();

		if (!$this->allowDelete([$key => $id], $key))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', 403);
		}

		// AdminModel::delete() takes its argument by reference.
		$recordIds = [$id];

		if (!$model->delete($recordIds))
		{
			if ($model->getError() !== false)
			{
				throw new \RuntimeException($model->getError(), 500);
			}

			$session = $this->app->getSession();
			$message = $session->get('http_status_code_409', false)
				? 'Resource not in state that can be deleted, must be trashed before it can be deleted'
				: Text::_('JLIB_APPLICATION_ERROR_DELETE');

			$session->clear('http_status_code_409');

			// The delete was refused without an error (e.g. vetoed by a plugin), so it must not be reported as success.
			$this->sendStatusAndClose(409, 'Conflict', $message);
		}

		if ($this->app->getSession()->get('http_status_code_404', false))
		{
			$this->app->getSession()->clear('http_status_code_404');

			$this->sendStatusAndClose(404, 'not found', 'Resource not found');
		}

		$this->app->setHeader('status', 204);
	}

	/**
	 * Sends a JSON status response and ends the request, as Joomla's ApiController::delete() does.
	 *
	 * @param   int     $code     HTTP status code.
	 * @param   string  $status   Short status text.
	 * @param   string  $message  Human-readable message.
	 *
	 * @return  void
	 * @since   7.5.3
	 */
	private function sendStatusAndClose(int $code, string $status, string $message): void
	{
		$this->app->setHeader('status', $code, true);
		$this->app->setHeader('Content-Type', 'application/json');
		$this->app->sendHeaders();

		echo new JsonResponse(['status' => $status, 'code' => $code, 'message' => $message]);

		$this->app->close();
	}
}
