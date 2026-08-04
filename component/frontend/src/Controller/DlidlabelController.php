<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelController as AdminDlidlabelController;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerReturnURLTrait;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use RuntimeException;
use Throwable;

class DlidlabelController extends AdminDlidlabelController
{
	use ControllerReturnURLTrait {
		ControllerReturnURLTrait::getRedirectToItemAppend as applyReturnURLOnItemAppend;
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   7.0.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$ret    = $this->applyReturnURLOnItemAppend($recordId, $urlVar);
		$Itemid = $this->input->get('Itemid', null);

		if (is_numeric($Itemid) && ($Itemid > 0))
		{
			$ret .= '&Itemid=' . urlencode((int) $Itemid);
		}

		return $ret;
	}

	protected function allowAdd($data = [])
	{
		if (parent::allowAdd($data))
		{
			return true;
		}

		if (empty($data))
		{
			return true;
		}

		if (($data['id'] ?? 0) !== 0)
		{
			return false;
		}

		$user_id = $data['user_id'] ?? null;

		if (is_null($user_id))
		{
			return true;
		}

		$user = $this->app->getIdentity();

		return ($user->guest != 1) && ($user_id == $user->id);
	}

	protected function onBeforeExecute(&$task)
	{
		$this->assertCanAccessRequestedRecord();

		$returnUrl                  = $this->getReturnUrl();
		$this->getView()->returnURL = $returnUrl ?: base64_encode(Route::_('index.php?option=com_ars&view=dlidlabels'));
	}

	/**
	 * Make sure the current user is allowed to access the record they asked for.
	 *
	 * Guests are always rejected: Download IDs belong to user records, therefore a guest can neither own nor create
	 * one.
	 *
	 * Logged in users can only ever touch their own records. This has to be enforced here, before the task runs, and
	 * not just in allowEdit(). The default task of a FormController is display, which renders the record loaded from
	 * the request without going through any of the allow*() methods.
	 *
	 * @return  void
	 * @throws  RuntimeException  When access is denied
	 * @since   7.5.0
	 */
	private function assertCanAccessRequestedRecord(): void
	{
		$user = $this->app->getIdentity();

		if ($user->guest)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$id = $this->input->getInt('id', 0);

		// No record requested: this is a new record, which is always allowed.
		if ($id <= 0)
		{
			return;
		}

		// Component administrators are allowed to manage everyone's Download IDs, same as in allowEdit().
		if ($user->authorise('core.admin', $this->option))
		{
			return;
		}

		/** @var DlidlabelModel $model */
		$model = $this->getModel();

		try
		{
			$record = $model->getItem($id);
		}
		catch (Throwable $e)
		{
			$record = null;
		}

		// A missing record is treated the same as someone else's record: denied, without disclosing which is which.
		if (!is_object($record) || empty($record->id ?? null) || ($record->user_id != $user->id))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	protected function onAfterExecute($task)
	{
		switch ($task)
		{
			case 'main':
			case 'edit':
			case 'add':
				break;

			default:
				$this->applyReturnUrl();
				break;
		}
	}
}