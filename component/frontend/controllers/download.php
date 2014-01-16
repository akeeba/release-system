<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDownload extends FOFController
{
    public function __construct($config = array())
    {
		parent::__construct($config);

        $this->cacheableTasks = array();
	}

	public function execute($task) {
		$task = 'download';

		parent::execute($task);
	}

	public function download($cachable = false, $urlparams = false)
	{
		$id = $this->input->getInt('id', null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');

		// Get the model
		$model = $this->getThisModel();

		// Log in a user if I have to
		$model->loginUser();

		// Get the log table
		$log = FOFModel::getTmpInstance('Logs','ArsModel')->getTable();

		// Get the item lists
		if($id > 0) {
			$item = $model->getItem($id);
		} else {
			$item = null;
		}

		if(is_null($item))
		{
			$log->save(array('authorized' => 0));
			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
		}
		elseif($item === -1 && $id)
		{
			$redirect = '';

			// I have to redirect the user, let's search where
			$tmpItem = FOFModel::getTmpInstance('Items', 'ArsModel')->getTable();
			$tmpItem->load($id);

			if($tmpItem->redirect_unauth)
			{
				$redirect = $tmpItem->redirect_unauth;
			}
			else
			{
				$release = FOFModel::getTmpInstance('Releases', 'ArsModel')->getTable();
				$release->load($tmpItem->release_id);

				// Do I have a redirect set on the release?
				if($release->redirect_unauth)
				{
					$redirect = $release->redirect_unauth;
				}
				else
				{
					$category = FOFModel::getTmpInstance('Categories', 'ArsModel')->getTable();
					$category->load($release->category_id);

					if($category->redirect_unauth)
					{
						$redirect = $category->redirect_unauth;
					}
				}
			}

			// Do I have a redirect set? If not, throw an error
			if($redirect)
			{
				JFactory::getApplication()->redirect($redirect);
			}
			{
				$log->save(array('authorized' => 0));
				return JError::raiseError(403, JText::_('ACCESS FORBIDDEN') );
			}
		}

		$item->hit();
		$log->save(array(
			'item_id' => $id,
			'authorized' => 1
			)
		);

		$model->doDownload();

		// No need to return anything; doDownload() calls the exit() method of the application object
	}
}