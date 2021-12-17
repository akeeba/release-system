<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelController as AdminDlidlabelController;
use Joomla\CMS\Router\Route;

class DlidlabelController extends AdminDlidlabelController
{
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
		$returnUrl                  = $this->getReturnUrl();
		$this->getView()->returnURL = $returnUrl ?: base64_encode(Route::_('index.php?option=com_ars&view=dlidlabels'));
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