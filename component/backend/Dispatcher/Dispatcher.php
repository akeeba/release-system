<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Dispatcher;

use FOF30\Container\Container;
use FOF30\Dispatcher\Mixin\ViewAliases;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	use ViewAliases
	{
		onBeforeDispatch as onBeforeDispatchViewAliases;
	}

	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->viewNameAliases = [
			'cpanel' => 'ControlPanel',
		];
	}

	public function onBeforeDispatch(): bool
	{
		/**
		 * Set up a media version. DO NOT REMOVE. There's something wrong on our site's extension cache which makes the
		 * FOF MediaVersion class return a new media version query string on every request :@
		 */
		$this->container->mediaVersion = ApplicationHelper::getHash(
			filemtime($this->container->frontEndPath . '/ars.php') . ':' . filemtime($this->container->backEndPath . '/ars.php')
		);

		$this->onBeforeDispatchViewAliases();

		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

		// Renderer options (0=none, 1=frontend, 2=backend, 3=both)
		$useFEF   = in_array($this->container->params->get('load_fef', 3), [2, 3]);
		$fefReset = $useFEF && in_array($this->container->params->get('fef_reset', 3), [2, 3]);

		if (!$useFEF)
		{
			$this->container->rendererClass = '\\FOF30\\Render\\Joomla3';
		}

		$darkMode = $this->container->params->get('dark_mode_backend', -1);

		$customCss = 'media://com_ars/css/backend.css';

		if ($darkMode != 0)
		{
			$customCss .= ', media://com_ars/css/backend_dark.css';
		}

		$this->container->renderer->setOptions([
			'load_fef'      => $useFEF,
			'fef_reset'     => $fefReset,
			'fef_dark'      => $useFEF ? $darkMode : 0,
			'custom_css'    => $customCss,
			// Render submenus as drop-down navigation bars powered by Bootstrap
			'linkbar_style' => 'classic',
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
		$this->container->template->addJS('media://com_ars/js/Modal.min.js', false, false, $mediaVersion);
		// Do not move: System depends on Ajax
		$this->container->template->addJS('media://com_ars/js/Ajax.min.js', false, false, $mediaVersion);
		// Do not move: System depends on Ajax
		$this->container->template->addJS('media://com_ars/js/System.min.js', false, false, $mediaVersion);
		// Do not move: Tooltip depends on System
		$this->container->template->addJS('media://com_ars/js/Tooltip.min.js', false, false, $mediaVersion);
	}
}
