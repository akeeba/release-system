<?php

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class ArsControllerUpdate extends F0FController
{

	public function execute($task)
	{
		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$task = $this->input->getCmd('task', '');
		$layout = $this->input->getCmd('layout', '');
		$id = $this->input->getInt('id', null);

		// Check for menu items bearing layout instead of task
		if ((empty($task) || ($task == 'read') || ($task == 'add')) && !empty($layout))
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
				return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
			}
			elseif ($viewType == 'ini')
			{
				$task = 'ini';
			}
			elseif (($viewType == 'raw') && empty($id))
			{
				return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
			}
			elseif ($viewType == 'raw')
			{
				$task = 'download';
			}
			else
			{
				$task = 'ini';
				$viewType = 'ini';
			}
		}
		elseif ($task == 'ini')
		{
			$viewType = 'ini';
		}
		elseif ($task == 'download')
		{
			$viewType = 'raw';
		}
		else
		{
			$viewType = 'xml';
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
			case 'xml':
				switch ($task)
				{
					case 'all':
						$task = 'all';
						break;

					case 'category':
						$task = 'category';
						break;

					case 'stream':
						$task = 'stream';
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

		parent::execute($task);
	}

	public function all()
	{
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

	public function category()
	{
		$cat = $this->input->getCmd('id', '');
		if (empty($cat))
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$cat = $params->get('category', 'components');
		}
		if (empty($cat))
		{
			return JError::raiseError(500, JText::_('ARS_ERR_NOUPDATESOURCE'));
		}
		$model = $this->getThisModel();
		$x = $model->getCategoryItems($cat);

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

	public function stream()
	{
		$id = $this->input->getInt('id', 0);
		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

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

	public function jed()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}

		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

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

	public function ini()
	{
		$id = $this->input->getInt('id', 0);
		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}
		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

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

	public function download()
	{
		$id = $this->input->getInt('id', 0);

		if ($id == 0)
		{
			// Do we have a menu item parameter?
			$app = JFactory::getApplication();
			$params = $app->getPageParameters('com_ars');
			$id = $params->get('streamid', 0);
		}

		$model = $this->getThisModel();
		$model->getItems($id);
		$model->getPublished($id);

		$items = $model->items;

		if (!$model->published)
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
		$dlitem = array_shift($items);

		// Get the download model
		$dl_model = F0FModel::getTmpInstance('Downloads', 'ArsModel');

		// Log in a user if I have to
		$dl_model->loginUser();

		// Get the log table
		$log = F0FModel::getTmpInstance('Logs', 'ArsModel')->getTable();

		// Get the item lists
		if ($dlitem->item_id > 0)
		{
			$item = $dl_model->getItem($dlitem->item_id);
		}
		else
		{
			$item = null;
		}

		if (is_null($item))
		{
			$log->save(array('authorized' => 0));

			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN'));
		}
		elseif ($item === -1 && $dlitem->id)
		{
			$log->save(array('authorized' => 0));

			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN'));
		}

		$item->hit();
		$log->save(array(
				'item_id'    => $dlitem->item_id,
				'authorized' => 1
			)
		);

		$dl_model->doDownload();
		// No need to return anything; doDownload() calls the exit() method of the application object
	}
}