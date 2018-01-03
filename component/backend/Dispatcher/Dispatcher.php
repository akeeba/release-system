<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Dispatcher;

defined('_JEXEC') or die;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	public function onBeforeDispatch()
	{
		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_ars/css/backend.css', $this->container->mediaVersion);
	}
}
