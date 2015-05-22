<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Helper\ComponentParams;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'Browse';

	public $viewMap = [
		'browses'    => 'Browse',
		'browse'     => 'Browse',
		'categories' => 'Category',
		'category'   => 'Category',
		'download'   => 'Download',
		'downloads'  => 'Download',
		'latest'     => 'Latest',
		'latests'    => 'Latest',
		'release'    => 'Releases',
		'releases'   => 'Releases',
		'update'     => 'Updates',
		'updates'    => 'Updates',
		'item'       => 'Item',
		'items'      => 'Item',
		'dlidlabel'  => 'DownloadIDLabel',
		'dlidlabels' => 'DownloadIDLabels'
	];

	public function onBeforeDispatch()
	{
		// Load Akeeba Strapper, if it is installed
		\JLoader::import('joomla.filesystem.folder');

		$useStrapper = ComponentParams::getParam('usestrapper', 3);

		if (in_array($useStrapper, [1, 3]) && \JFolder::exists(JPATH_SITE . '/media/strapper30'))
		{
			@include_once JPATH_SITE . '/media/strapper30/strapper.php';

			if (class_exists('\\AkeebaStrapper30', false))
			{
				\AkeebaStrapper30::bootstrap();
			}
		}

		// Map the view
		$this->applyViewMap();

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_akeebasubs/css/frontend.css', $this->container->mediaVersion);
	}

	/**
	 * Applies the view mapping. This allows URLs of ARS 1.x / 2.x to still work with the new version.
	 *
	 * @return  void
	 */
	public function applyViewMap()
	{
		$view = $this->container->input->getCmd('view', 'Browse');

		if (empty($view))
		{
			$view = 'Browse';
		}

		if (array_key_exists($view, $this->viewMap))
		{
			$view = $this->viewMap[$view];
		}

		if (!in_array($view, $this->viewMap))
		{
			$view = 'Browse';
		}
		$this->container->input->set('view', $view);
	}
}