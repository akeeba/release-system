<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelsController as AdminDlidlabelsController;
use Joomla\CMS\Language\Text;
use RuntimeException;

class DlidlabelsController extends AdminDlidlabelsController
{
	public function onBeforeExecute(&$task)
	{
		$user = $this->app->getIdentity();

		if ($user->guest)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
		}
	}

	public function getModel($name = 'Dlidlabel', $prefix = 'Site', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

}