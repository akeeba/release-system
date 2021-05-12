<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Dispatcher;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Dispatcher\Dispatcher as BackendDispatcher;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;

class Dispatcher extends BackendDispatcher
{
	protected $defaultController = 'categories';

	protected $viewMap = [
		'downloadidlabel'  => 'dlidlabel',
		'downloadidlabels' => 'dlidlabels',
		'updates'          => 'update',
		'latests'          => 'latest',
	];

	protected function loadCommonStaticMedia()
	{
		// Make sure we run under a CMS application
		if (!($this->app instanceof CMSApplication))
		{
			return;
		}

		// Make sure the document is HTML
		$document = $this->app->getDocument();

		if (!($document instanceof HtmlDocument))
		{
			return;
		}

		// Finally, load our 'common' preset
		$document->getWebAssetManager()
			->usePreset('com_ars.frontend');
	}
}