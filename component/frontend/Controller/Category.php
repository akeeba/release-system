<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\DataController;

class Category extends DataController
{
	public function execute($task)
	{
		// If we're using the JSON API we need a manager
		$format = $this->input->getCmd('format', 'html');

		if (!in_array($format, ['html', 'feed']) && !($this->checkACL('core.manage') || $this->checkACL('core.admin')))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		if ($task == 'default')
		{
			$task = $this->getCrudTask();
		}

		// For the HTML view we only allow browse or read (default: read)
		if (in_array($format, ['html', 'feed']))
		{
			if (!in_array($task, ['browse', 'read']))
			{
				$task = 'browse';
			}
		}

		return parent::execute($task);
	}

	public function onBeforeBrowse()
	{
		// Apply one of the allowed layouts
		if (!in_array($this->layout, ['normal', 'bleedingedge', 'repository']))
		{
			$this->layout = 'repository';
		}

		// Push page parameters to the model
		/** @var \JApplicationSite $app */
		$app    = \JFactory::getApplication();
		$params = $app->getParams('com_ars');

		/** @var \Akeeba\ReleaseSystem\Site\Model\Categories $model */
		$model = $this->getModel();
		$model->orderby($params->get('orderby', 'order'))
			  ->limitstart(0)
			  ->limit(0);
	}
}