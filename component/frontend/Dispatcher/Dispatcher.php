<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Dispatcher;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class Dispatcher extends \FOF40\Dispatcher\Dispatcher
{
	/**
	 * Map old views to the new ones.
	 *
	 * @var array
	 */
	public $viewMap = [
		'browses'    => 'Categories',
		'browse'     => 'Categories',
		'categories' => 'Categories',
		'category'   => 'Releases',
		'release'    => 'Items',
		'releases'   => 'Releases',
		'download'   => 'Item',
		'downloads'  => 'Item',
		'Download'   => 'Item',
		'item'       => 'Item',
		'items'      => 'Items',
		'latest'     => 'Latest',
		'latests'    => 'Latest',
		'update'     => 'Update',
		'updates'    => 'Update',
		'Updates'    => 'Update',
		'dlidlabel'  => 'DownloadIDLabel',
		'dlidlabels' => 'DownloadIDLabels'
	];

	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'Categories';

	public function onBeforeDispatch(): void
	{
		/**
		 * Set up a media version. DO NOT REMOVE. There's something wrong on our site's extension cache which makes the
		 * FOF MediaVersion class return a new media version query string on every request :@
		 */
		$this->container->mediaVersion = ApplicationHelper::getHash(
			filemtime($this->container->frontEndPath . '/ars.php') . ':' . filemtime($this->container->backEndPath . '/ars.php')
		);

		// Map the view
		$this->applyViewMap();

		// Renderer options (0=none, 1=frontend, 2=backend, 3=both)
		$useFEF   = in_array($this->container->params->get('load_fef', 3), [1, 3]);
		$fefReset = $useFEF && in_array($this->container->params->get('fef_reset', 3), [1, 3]);

		if (!$useFEF)
		{
			$this->container->rendererClass = '\\FOF40\\Render\\Joomla3';
		}

		$darkMode = $this->container->params->get('dark_mode_frontend', -1);

		$customCss = 'media://com_ars/css/frontend.css';

		if ($darkMode != 0)
		{
			$customCss .= ', media://com_ars/css/frontend_dark.css';
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
	}

	/**
	 * Applies the view mapping. This allows URLs of ARS 1.x / 2.x to still work with the new version.
	 *
	 * @return  void
	 */
	public function applyViewMap(): void
	{
		$view = $this->container->input->getCmd('view', 'Categories');

		if (empty($view))
		{
			$view = 'Categories';
		}

		if (array_key_exists($view, $this->viewMap))
		{
			$view = $this->viewMap[$view];
		}

		if (!in_array($view, $this->viewMap))
		{
			$view = 'Categories';
		}

		$this->container->input->set('view', $view);
	}

	private function loadCommonJavascript(): void
	{
		\AkeebaFEFHelper::loadFEFScript('Tooltip');
	}
}
