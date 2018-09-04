<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Dispatcher;

defined('_JEXEC') or die;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
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

	public function onBeforeDispatch()
	{
		// Map the view
		$this->applyViewMap();

        // Renderer options (0=none, 1=frontend, 2=backend, 3=both)
        $useFEF   = $this->container->params->get('load_fef', 3);
        $fefReset = $this->container->params->get('fef_reset', 3);

        $this->container->renderer->setOption('load_fef', in_array($useFEF, [1,3]));
        $this->container->renderer->setOption('fef_reset', in_array($fefReset, [1,3]));
        $this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_ars/css/frontend.css', $this->container->mediaVersion);
	}

	/**
	 * Applies the view mapping. This allows URLs of ARS 1.x / 2.x to still work with the new version.
	 *
	 * @return  void
	 */
	public function applyViewMap()
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
}
