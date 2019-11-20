<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Dispatcher;

use FOF30\Container\Container;
use FOF30\Dispatcher\Mixin\ViewAliases;
use Joomla\CMS\HTML\HTMLHelper;

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

	public function onBeforeDispatch(): bool
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

		// FEF Renderer options. Used to load the common CSS file.
		$this->container->renderer->setOptions([
			'custom_css' => 'media://com_ars/css/backend.css',
		]);

		$format = $this->input->getCmd('format', 'html');

		if ($format == 'html')
		{
			// Load common Javascript files.
			$this->loadCommonJavascript();
		}

		return true;
	}

	private function loadCommonJavascript(): void
	{
		// Load common CSS and JavaScript
		HTMLHelper::_('jquery.framework');

		$mediaVersion = $this->container->mediaVersion;

		// Do not move: System depends on Modal
		$this->container->template->addJS('media://com_ars/js/Modal.js', false, false, $mediaVersion);
		// Do not move: System depends on Ajax
		$this->container->template->addJS('media://com_ars/js/Ajax.js', false, false, $mediaVersion);
		// Do not move: System depends on Ajax
		$this->container->template->addJS('media://com_ars/js/System.js', false, false, $mediaVersion);
		// Do not move: Tooltip depends on System
		$this->container->template->addJS('media://com_ars/js/Tooltip.js', false, false, $mediaVersion);
	}
}
