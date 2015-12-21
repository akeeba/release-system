<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Helper\ComponentParams;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	public function onBeforeDispatch()
	{
		// Load Akeeba Strapper, if it is installed
		\JLoader::import('joomla.filesystem.folder');

		$useStrapper = ComponentParams::getParam('usestrapper', 3);

		if (in_array($useStrapper, [2, 3]) && \JFolder::exists(JPATH_SITE . '/media/strapper30'))
		{
			@include_once JPATH_SITE . '/media/strapper30/strapper.php';

			if (class_exists('\\AkeebaStrapper30', false))
			{
				\AkeebaStrapper30::bootstrap();
			}
		}
		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_ars/css/backend.css', $this->container->mediaVersion);
		$this->container->template->addJS('media://com_ars/js/backend.js', false, false, $this->container->mediaVersion);
	}
}