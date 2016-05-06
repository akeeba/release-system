<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Helper\ComponentParams;

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