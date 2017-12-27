<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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

		// For the HTML view we only allow browse
		if (in_array($format, ['html', 'feed']))
		{
			$task = 'browse';
		}

		return parent::execute($task);
	}

	/**
	 * Overrides the default display method to add caching support
	 *
	 * @param   bool        $cachable  Is this a cacheable view?
	 * @param   bool|array  $urlparams Registered URL parameters
	 * @param   null|string $tpl       Sub-template (not really used...)
	 */
	public function display($cachable = false, $urlparams = false, $tpl = null)
	{
		$cachable = true;

		if (!is_array($urlparams))
		{
			$urlparams = [];
		}

		$additionalParams = array(
			'option'      => 'CMD',
			'view'        => 'CMD',
			'task'        => 'CMD',
			'format'      => 'CMD',
			'layout'      => 'CMD',
			'id'          => 'INT',
			'vgroupid'    => 'INT',
		);

		$urlparams = array_merge($additionalParams, $urlparams);

		parent::display($cachable, $urlparams, $tpl);
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
		$model->orderby_filter($params->get('orderby', 'order'))
			  ->limitstart(0)
			  ->limit(0)
			  ->published(1)
			  ->access_user($this->container->platform->getUser()->id);
	}
}
