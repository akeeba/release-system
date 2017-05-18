<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Model\Download;
use Akeeba\ReleaseSystem\Site\Model\Items;
use Akeeba\ReleaseSystem\Site\Model\Logs;
use Akeeba\ReleaseSystem\Site\Model\Update as UpdateModel;
use Akeeba\ReleaseSystem\Site\View\Update\Ini;
use FOF30\Controller\Controller;
use JFactory;

class Update extends Controller
{
	/**
	 * Determines the task from the layout and view format
	 *
	 * @param   string  $task  The task to execute
	 *
	 * @return  void
	 */
	public function execute($task)
	{
		$viewType = $this->container->platform->getDocument()->getType();
		$task     = $this->input->getCmd('task', '');
		$layout   = $this->input->getCmd('layout', '');
		$id       = $this->input->getInt('id', null);

		// Check for menu items bearing layout instead of task
		if ((empty($task) || ($task == 'main') && !empty($layout)))
		{
			$task = $layout;
		}

		// Check for default task
		if (empty($task) || ($task == 'read') || ($task == 'add'))
		{
			if ($viewType == 'xml')
			{
				$task = 'all';
			}
			elseif (($viewType == 'ini') && empty($id))
			{
				throw new \RuntimeException(\JText::_('ARS_ERR_NOUPDATESOURCE'), 500);
			}
			elseif ($viewType == 'ini')
			{
				$task = 'ini';
			}
			elseif (($viewType == 'raw') && empty($id))
			{
				throw new \RuntimeException(\JText::_('ARS_ERR_NOUPDATESOURCE'), 500);
			}
			elseif ($viewType == 'raw')
			{
				$task = 'download';
			}
			else
			{
				$task = 'ini';
			}
		}

		switch ($task)
		{
			case 'ini':
				$viewType = 'ini';
				break;

			case 'download':
				$viewType = 'raw';
				break;

			default:
				$viewType = 'xml';
				break;
		}

		switch ($viewType)
		{
			default:
			case 'xml':
				switch ($task)
				{
					default:
					case 'all':
						$task = 'all';
						break;

					case 'category':
						$task = 'category';
						break;

					case 'stream':
						$task = 'stream';
						break;

					case 'jed':
						$task = 'jed';
						break;
				}
				break;

			case 'ini':
				$task = 'ini';
				break;

			case 'raw':
				$task = 'download';
				break;
		}

		$this->input->set('task', $task);

		parent::execute($task);
	}

	/**
	 * Show all updates
	 */
	public function all()
	{
		$registeredURLParams = array(
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'CMD',
			'dlid'   => 'STRING',
		);

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Show updates for a category
	 *
	 * @throws  \RuntimeException
	 */
	public function category()
	{
		$cat = $this->input->getCmd('id', '');

		if (empty($cat))
		{
			// Do we have a menu item parameter?
			/** @var \JApplicationSite $app */
			$app    = JFactory::getApplication();
			$params = $app->getParams('com_ars');
			$cat    = $params->get('category', 'components');
		}

		if (empty($cat))
		{
			throw new \RuntimeException(\JText::_('ARS_ERR_NOUPDATESOURCE'), 500);
		}

		// Required for caching
		$this->input->set('id', $cat);

		/** @var UpdateModel $model */
		$model       = $this->getModel();
		$view        = $this->getView();
		$view->items = $model->getCategoryItems($cat);

		$registeredURLParams = array(
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		);

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Show an update stream
	 *
	 * @throws \RuntimeException
	 */
	public function stream()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			/** @var \JApplicationSite $app */
			$app    = JFactory::getApplication();
			$params = $app->getParams('com_ars');
			$id     = $params->get('streamid', 0);

			// Define the Id for caching as if it were received as a safeuri param
			JFactory::getApplication()->input->set('id', $id);
		}

		/** @var UpdateModel $model */
		$model           = $this->getModel();
		$view            = $this->getView();
		$view->items     = $model->getItems($id);
		$view->published = $model->getPublished($id);

		$registeredURLParams = array(
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		);

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Generates the JED listing update XML file
	 *
	 * @throws \RuntimeException
	 */
	public function jed()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			/** @var \JApplicationSite $app */
			$app    = JFactory::getApplication();
			$params = $app->getParams('com_ars');
			$id     = $params->get('streamid', 0);
		}

		// Required for caching
		$this->input->set('id', $id);

		/** @var UpdateModel $model */
		$model           = $this->getModel();
		$view            = $this->getView();
		$view->items     = $model->getItems($id);
		$view->published = $model->getPublished($id);

		$registeredURLParams = array(
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		);

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Show an INI formatted update stream
	 *
	 * @throws \RuntimeException
	 */
	public function ini()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			/** @var \JApplicationSite $app */
			$app    = JFactory::getApplication();
			$params = $app->getParams('com_ars');
			$id     = $params->get('streamid', 0);
		}

		// Required for caching
		$this->input->set('id', $id);

		/** @var UpdateModel $model */
		$model           = $this->getModel();
		/** @var Ini $view */
		$view            = $this->getView();
		$view->items     = $model->getItems($id);
		$view->published = $model->getPublished($id);

		$registeredURLParams = array(
			'option' => 'CMD',
			'view'   => 'CMD',
			'task'   => 'CMD',
			'format' => 'CMD',
			'layout' => 'CMD',
			'id'     => 'INT',
			'dlid'   => 'STRING',
		);

		$this->display(true, $registeredURLParams);
	}

	/**
	 * Downloads the latest version of a software given its update stream ID
	 *
	 * @throws \RuntimeException
	 */
	public function download()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			/** @var \JApplicationSite $app */
			$app    = JFactory::getApplication();
			$params = $app->getParams('com_ars');
			$id     = $params->get('streamid', 0);
		}

		// Required for caching
		$this->input->set('id', $id);

		/** @var UpdateModel $model */
		$model     = $this->getModel();
		$items     = $model->getItems($id);
		$published = $model->getPublished($id);

		if (!$published)
		{
			// This stream isn't published. Go away! GO. AWAY.
			die();
		}

		if (empty($items))
		{
			// No items to display. What are you doing here? Are you lost? Go away!
			die();
		}

		// Get the download item
		$downloadItem = array_shift($items);

		// Get the download model
		/** @var Download $downloadModel */
		$downloadModel = $this->container->factory->model('Download')->tmpInstance();
		/** @var Items $item */
		$item = $this->container->factory->model('Items')->tmpInstance();

		try
		{
			if ($downloadItem->item_id <= 0)
			{
				throw new \Exception('No item');
			}

			$item->find($downloadItem->item_id);
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Log in a user if I have to
		$downloadModel->loginUser();

		// Get the log table
		/** @var Logs $log */
		$log = $this->container->factory->model('Logs')->tmpInstance();

		if (!Filter::filterItem($item))
		{
			$log->create([
				'authorized' => 0,
				'item_id'    => $downloadItem->item_id
			]);

			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		$item->save([
			'hits' => ++$item->hits
		]);

		$log->create([
			'item_id'    => $downloadItem->item_id,
			'authorized' => 1
		]);

		$downloadModel->doDownload($item);

		$this->container->platform->closeApplication();
	}
}