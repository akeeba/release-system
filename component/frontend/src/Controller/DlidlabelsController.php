<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelsController as AdminDlidlabelsController;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerEvents;
use Akeeba\Component\ARS\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\ARS\Site\Mixin\ControllerDisplayTrait;
use Akeeba\Component\ARS\Site\View\Dlidlabels\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use RuntimeException;

class DlidlabelsController extends AdminDlidlabelsController
{
	use ControllerEvents;
	use ControllerRegisterTasksTrait;
	use ControllerDisplayTrait;

	public function getModel($name = 'Dlidlabel', $prefix = 'Site', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function onBeforeDisplay(&$cachable, &$urlparams)
	{
		$cachable = false;

		/** @var HtmlView $view */
		$view           = $this->getView();
		$view->Itemid   = $this->input->get('Itemid', null, 'int');
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