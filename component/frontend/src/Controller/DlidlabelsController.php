<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelsController as AdminDlidlabelsController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use RuntimeException;

class DlidlabelsController extends AdminDlidlabelsController
{
	public function getModel($name = 'Dlidlabel', $prefix = 'Site', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function onBeforeExecute(&$task)
	{
		$user = $this->app->getIdentity();

		if ($user->guest)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$returnUrl                  = $this->getReturnUrl();
		$this->getView()->returnURL = $returnUrl ?: base64_encode(Uri::current());
	}

	protected function onAfterExecute($task)
	{
		$this->applyReturnUrl();
	}

	public function checkToken($method = 'request', $redirect = true)
	{
		return parent::checkToken($method, $redirect);
	}
}