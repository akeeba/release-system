<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\BleedingEdge;
use Akeeba\ReleaseSystem\Site\Model\Download;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Logs;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use FOF30\Controller\DataController;

class Item extends DataController
{
	public function execute($task)
	{
		// If we're using the JSON API we need a manager
		$format = $this->input->getCmd('format', 'html');

		if (($format == 'json') && !($this->checkACL('core.manage') || $this->checkACL('core.admin')))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// For the HTML view we only allow browse and download
		if ($format != 'json')
		{
			if (!in_array($task, ['browse', 'download']))
			{
				$task = 'browse';
			}
		}

		return parent::execute($task);
	}

	/**
	 * Overrides the default display method to add caching support
	 *
	 * @param   bool         $cachable   Is this a cacheable view?
	 * @param   bool|array   $urlparams  Registered URL parameters
	 * @param   null|string  $tpl        Sub-template (not really used...)
	 */
	public function display($cachable = false, $urlparams = false, $tpl = null)
	{
		$cachable = true;

		if (!is_array($urlparams))
		{
			$urlparams = [];
		}

		$additionalParams = array(
			'option'     => 'CMD',
			'view'       => 'CMD',
			'task'       => 'CMD',
			'format'     => 'CMD',
			'layout'     => 'CMD',
			'release_id' => 'INT',
			'id'         => 'INT',
			'dlid'       => 'STRING',
		);

		$urlparams = array_merge($additionalParams, $urlparams);

		// Do not cache filterable views
		$layout = $this->input->getCmd('layout', 'default');
		$tmpl = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$cachable = false;
		}

		parent::display($cachable, $urlparams, $tpl);
	}

	public function onBeforeBrowse()
	{
		// Only apply on HTML views
		if (!in_array($this->input->getCmd('format', 'html'), ['html', 'feed']))
		{
			return;
		}

		$layout = $this->input->getCmd('layout', 'default');
		$tmpl = $this->input->getCmd('tmpl', '');

		if (($layout == 'modal') && ($tmpl == 'component'))
		{
			$this->onBeforeBrowseModal();

			return;
		}

		// Get the page parameters
		/** @var \JApplicationSite $app */
		$app    = \JFactory::getApplication();
		$params = $app->getParams('com_ars');

		// Push the page params to the Items model
		/** @var Releases $releaseModel */
		$releaseModel = $this->getModel('Releases');
		$releaseModel
							 ->orderby_filter($params->get('rel_orderby', 'order'))
							 ->category_id(0)
							 ->access_user($this->container->platform->getUser()->id);

		/** @var Items $itemsModel */
		$itemsModel = $this->getModel();
		$itemsModel
						   ->orderby_filter($params->get('items_orderby', 'order'))
						   ->release_id(0)
						   ->access_user($this->container->platform->getUser()->id);

		// Get the release ID
		$id = $this->input->getInt('release_id', 0);

		if (empty($id))
		{
			$id = $params->get('relid', 0);
		}

		// Required for caching
		$this->input->set('relid', null);
		$this->input->set('release_id', $id);

		try
		{
			// Try to find the release
			$releaseModel->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($releaseModel) || !$releaseModel->published)
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($releaseModel->id && $releaseModel->redirect_unauth && $releaseModel->show_unauth_links)
			{
				$noAccessURL = $releaseModel->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		try
		{
			// Try to filter the category as well
			$category = $releaseModel->category;

			if (!Filter::filterItem($category) || !$category->published)
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($releaseModel->category->id && $releaseModel->category->redirect_unauth && $releaseModel->category->show_unauth_links)
			{
				$noAccessURL = $releaseModel->category->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Filter the releases by this category
		$itemsModel->release($releaseModel->id)->published(1);

		/** @var BleedingEdge $bleedingEdgeModel */
		$bleedingEdgeModel = $this->container->factory->model('BleedingEdge');
		$bleedingEdgeModel->checkFiles($releaseModel);

		// Push the models to the view
		$this->getView()->setDefaultModel($itemsModel);
		$this->getView()->setModel('Releases', $releaseModel);
	}

	public function onBeforeBrowseModal()
	{
	}

	/**
	 * Handles input data transformations before telling the Model to save them.
	 *
	 * @param   array  $data
	 *
	 * @return  void  The $data array is directly handled
	 */
	protected function onBeforeApplySave(&$data)
	{
		// If "groups" is a comma separated list of IDs convert to a proper array
		if (isset($data['groups']) && !is_array($data['groups']))
		{
			if (empty($data['groups']))
			{
				$data['groups'] = array();
			}

			if (!is_array($data['groups']))
			{
				$data['groups'] = explode(',', $data['groups']);
				$data['groups'] = array_map(function ($x) {
					return trim($x);
				}, $data['groups']);
			}
		}

		// If "environments" is a comma separated list of IDs convert to a proper array
		if (isset($data['environments']) && !is_array($data['environments']))
		{
			if (empty($data['environments']))
			{
				$data['environments'] = array();
			}

			if (!is_array($data['environments']))
			{
				$data['environments'] = explode(',', $data['environments']);
				$data['environments'] = array_map(function ($x) {
					return trim($x);
				}, $data['environments']);
			}
		}
	}

	public function download()
	{
		$id = $this->input->getInt('id', null);

		// Get the page parameters
		$app    = \JFactory::getApplication();
		$params = \JComponentHelper::getParams('com_ars');

		/** @var Download $model */
		$model = $this->getModel('Download');

		// Log in a user if I have to
		$model->loginUser();

		// Get the log table
		/** @var Logs $log */
		$log = $this->getModel('Logs');

		/** @var Items $item */
		$item = $this->getModel();

		// The item must exist and be accessible
		try
		{
			// Try to find the item
			$item->find($id);

			// Make sure subscription level filtering allows access
			if (!Filter::filterItem($item, false, $this->container->platform->getUser()->getAuthorisedViewLevels()))
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($item->id ? $id : 0);

			$model->logoutUser();

			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if ($item->id && $item->redirect_unauth && $item->show_unauth_links)
			{
				$noAccessURL = $item->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}


		try
		{
			// The release must be accessible
			$release = $item->release;

			// Make sure subscription level filtering allows access
			if (!$release->id || !Filter::filterItem($release, false, $this->container->platform->getUser()->getAuthorisedViewLevels()))
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($id);

			$model->logoutUser();

			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if (isset($release) && $release->id && $release->redirect_unauth && $release->show_unauth_links)
			{
				$noAccessURL = $release->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// The category must be accessible
		try
		{
			$category = $release->category;

			// Make sure subscription level filtering allows access
			if (!$category->id || !Filter::filterItem($category, false, $this->container->platform->getUser()->getAuthorisedViewLevels()))
			{
				throw new \Exception('Filtering failed');
			}
		}
		catch (\Exception $e)
		{
			$this->logFailedDownloadAttempt($id);

			$model->logoutUser();

			$noAccessURL = \JComponentHelper::getParams('com_ars')->get('no_access_url', '');

			if (isset($category) && $category->id && $category->redirect_unauth && $category->show_unauth_links)
			{
				$noAccessURL = $category->redirect_unauth;
			}

			if (!empty($noAccessURL))
			{
				$this->container->platform->redirect($noAccessURL);

				return;
			}

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Hit the item
		$item->save([
			'hits' => ++$item->hits
		]);

		// Log the download
		$log->create(array(
				'item_id'    => $id,
				'authorized' => 1
			)
		);

		// Download the item
		$model->doDownload($item);

		$this->container->platform->closeApplication();
	}

	private function logFailedDownloadAttempt($id)
	{
		$log = $this->getModel('Logs');

		$log->create(array(
				'item_id'    => $id,
				'authorized' => 0
			)
		);

		/** @var \JApplicationSite $app */
		$app    = \JFactory::getApplication();
		$params = \JComponentHelper::getParams('com_ars');

		if ($params->get('banUnauth', 0))
		{
			$extraMessage = $id ? 'Item : ' . $id : '';

			// Let's fire the system plugin event. If Admin Tools is installed, it will handle this and ban the user
			$app->triggerEvent('onAdminToolsThirdpartyException', array(
				'external',
				\JText::_('COM_ARS_BLOCKED_MESSAGE'),
				array($extraMessage)
			),
				true
			);
		}
	}
}
