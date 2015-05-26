<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;


use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\BleedingEdge;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF30\Controller\DataController;

class Item extends DataController
{
	public function execute($task)
	{
		// If we're using the JSON API we need a manager
		$format = $this->input->getCmd('format', 'html');

		if (!in_array($format, ['html', 'feed']) && !($this->checkACL('core.manage') || $this->checkACL('core.admin')))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// For the HTML view we only allow browse and download
		if (in_array($format, ['html', 'feed']))
		{
			if (!in_array($task, ['browse', 'download']))
			{
				$task = 'browse';
			}
		}

		return parent::execute($task);
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

		// Push the page params to the Items model
		/** @var Releases $releaseModel */
		$releaseModel = $this->getModel('Releases')
							 ->orderby($params->get('rel_orderby', 'order'))
							 ->access_user(\JFactory::getUser()->id);

		/** @var Items $itemsModel */
		$itemsModel = $this->getModel()
						   ->orderby($params->get('items_orderby', 'order'))
						   ->access_user(\JFactory::getUser()->id);

		// Get the release ID
		$id = $this->input->getInt('release_id', 0);

		if (empty($id))
		{
			$id = $params->get('relid', 0);
		}

		try
		{
			// Try to find the category
			$releaseModel->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($releaseModel))
			{
				throw new \Exception('Filtering failed');
			}

			// Filter the releases by this category
			$itemsModel->release($releaseModel->id);
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
		$bleedingEdgeModel->checkFiles($releaseModel);

		// Push the models to the view
		$this->getView()->setDefaultModel($itemsModel);
		$this->getView()->setModel('Releases', $releaseModel);
	}

}