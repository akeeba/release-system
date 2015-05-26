<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
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

	public function onBeforeBrowse()
	{
		$limitstart = $this->input->getInt('limitstart', -1);

		if ($limitstart >= 0)
		{
			$this->input->set('start', $limitstart);
		}

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
							  ->orderby($params->get('orderby', 'order'));

		/** @var Releases $releasesModel */
		$releasesModel = $this->getModel()
							  ->orderby($params->get('rel_orderby', 'order'));

		// Get the category ID
		$id = $this->input->getInt('category_id', 0);

		if (empty($id))
		{
			$id = $params->get('catid', 0);
		}

		try
		{
			// Try to find the category
			$categoryModel->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($categoryModel))
			{
				throw new \Exception('Filtering failed');
			}

			// Filter the releases by this category
			$releasesModel->category($categoryModel->id);
		}
		catch (\Exception $e)
		{
			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if (!empty($noAccessURL))
			{
				\JFactory::getApplication()->redirect($noAccessURL);
				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		/** @var BleedingEdge $bleedingEdgeModel */
		$bleedingEdgeModel = $this->container->factory->model('BleedingEdge');
		$bleedingEdgeModel->scanCategory($categoryModel);

		// Push the models to the view
		$this->getView()->setDefaultModel($releasesModel);
		$this->getView()->setModel('Categories', $categoryModel);
	}

}