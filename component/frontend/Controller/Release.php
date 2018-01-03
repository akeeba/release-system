<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\BleedingEdge;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF30\Controller\DataController;

class Release extends DataController
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
			'category_id' => 'INT',
			'id'          => 'INT',
			'dlid'        => 'STRING',
		);

		$urlparams = array_merge($additionalParams, $urlparams);

		parent::display($cachable, $urlparams, $tpl);
	}

	public function onBeforeBrowse()
	{
		// Only apply on HTML views
		if (!in_array($this->input->getCmd('format', 'html'), ['html', 'feed']))
		{
			return;
		}

		// Get the page parameters
		/** @var \JApplicationSite $app */
		$app    = \JFactory::getApplication();
		$params = $app->getParams('com_ars');

		// Push the page params to the Releases model
		/** @var Categories $categoryModel */
		$categoryModel = $this->getModel('Categories')
							  ->orderby_filter($params->get('orderby', 'order'))
							  ->access_user($this->container->platform->getUser()->id);

		/** @var Releases $releasesModel */
		$releasesModel = $this->getModel()
							  ->orderby_filter($params->get('rel_orderby', 'order'))
							  ->access_user($this->container->platform->getUser()->id);

		// Get the category ID
		$id = $this->input->getInt('category_id', 0);

		if (empty($id))
		{
			$id = $params->get('catid', 0);
		}

		// Required for caching
		$this->input->set('id', null);
		$this->input->set('category_id', $id);

		try
		{
			// Try to find the category
			$categoryModel->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($categoryModel) || !$categoryModel->published)
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($categoryModel->id && $categoryModel->redirect_unauth && $categoryModel->show_unauth_links)
			{
				$noAccessURL = $categoryModel->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Filter the releases by this category
		$releasesModel->category($categoryModel->id)->published(1);

		/** @var BleedingEdge $bleedingEdgeModel */
		$bleedingEdgeModel = $this->container->factory->model('BleedingEdge');
		$bleedingEdgeModel->scanCategory($categoryModel);

		// Push the models to the view
		$this->getView()->setDefaultModel($releasesModel);
		$this->getView()->setModel('Categories', $categoryModel);
	}

}
