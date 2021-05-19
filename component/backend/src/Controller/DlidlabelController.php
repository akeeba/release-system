<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use RuntimeException;

class DlidlabelController extends FormController
{
	use ControllerEvents;

	protected $text_prefix = 'COM_ARS_DLIDLABEL';

	protected function allowEdit($data = [], $key = 'id')
	{
		$user = $this->app->getIdentity();

		if ($user->authorise('core.admin', $this->option))
		{
			return true;
		}

		$id = (int) isset($data[$key]) ? $data[$key] : 0;

		if (!$id)
		{
			return false;
		}

		/** @var DlidlabelModel $model */
		$model  = $this->getModel();
		$record = $model->getItem($id);

		if ($record->user_id != $user->id)
		{
			return false;
		}

		if ($record->primary)
		{
			throw new RuntimeException(Text::_('COM_ARS_DLIDLABELS_ERR_CANTEDITDEFAULT'), 403);
		}

		return true;
	}

}