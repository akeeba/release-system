<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\Arsjed\Extension;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;

class Arsjed extends CMSPlugin
{
	/** @var CMSApplication */
	protected $app;

	public function onAfterInitialise()
	{
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			return;
		}

		$installat  = base64_decode($this->app->input->get('installat', null, 'base64'));
		$installapp = $this->app->input->get('installapp', null, 'int');

		if (!empty($installapp) && !empty($installat))
		{
			$session = $this->app->getSession();
			$session->set('arsjed.installat', $installat);
			$session->set('arsjed.installapp', $installapp);
		}
	}

}