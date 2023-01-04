<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerReturnURLTrait;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\ARS\Administrator\Model\DlidlabelModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use RuntimeException;

class DlidlabelController extends FormController
{
	use ControllerEvents;
	use ControllerReturnURLTrait;
	use ControllerReusableModelsTrait;

	protected $text_prefix = 'COM_ARS_DLIDLABEL';

	/**
	 * The URL view item variable.
	 *
	 * @var    string
	 * @since  7.0.5
	 */
	protected $view_item = 'dlidlabel';

	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  7.0.5
	 */
	protected $view_list = 'dlidlabels';

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