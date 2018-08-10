<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Dispatcher;

use FOF30\Container\Container;
use FOF30\Dispatcher\Mixin\ViewAliases;

defined('_JEXEC') or die;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	use ViewAliases {
		onBeforeDispatch as onBeforeDispatchViewAliases;
	}

	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->viewNameAliases = [
			'cpanel'             => 'ControlPanel',
		];
	}

	public function onBeforeDispatch()
	{
		$this->onBeforeDispatchViewAliases();

		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

        // Renderer options (0=none, 1=frontend, 2=backend, 3=both)
        $useFEF   = $this->container->params->get('load_fef', 3);
        $fefReset = $this->container->params->get('fef_reset', 3);

        $this->container->renderer->setOption('load_fef', in_array($useFEF, [2,3]));
        $this->container->renderer->setOption('fef_reset', in_array($fefReset, [2,3]));
        $this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_ars/css/backend.css', $this->container->mediaVersion);
	}
}
