<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDownload extends F0FController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->cacheableTasks = array();
	}

	public function execute($task)
	{
		$task = 'download';

		parent::execute($task);
	}

	public function download($cachable = false, $urlparams = false)
	{
		$id = $this->input->getInt('id', null);

		// Get the page parameters
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_ars');

		/** @var ArsModelDownloads $model */
		$model = $this->getThisModel();

		// Log in a user if I have to
		$model->loginUser();

		// Get the log table
		$log = F0FModel::getTmpInstance('Logs', 'ArsModel')->getTable();

		// Get the item lists
		if ($id > 0)
		{
			$item = $model->getItem($id);
		}
		else
		{
			$item = null;
		}

		if (is_null($item))
		{
			$log->save(array(
					'item_id'    => $id,
					'authorized' => 0
				)
			);

			if ($params->get('banUnauth', 0))
			{
				// Let's fire the system plugin event. If Admin Tools is installed, it will handle this and ban the user
				$app->triggerEvent('onAdminToolsThirdpartyException', array(
						'ARSscraper',
						JText::_('COM_ARS_BLOCKED_MESSAGE'),
						array('Item : ' . $id)
					),
					true
				);
			}

			return JError::raiseError(403, JText::_('ACCESS FORBIDDEN'));
		}
		elseif ($item === -1 && $id)
		{
			$redirect = '';

			// I have to redirect the user, let's search where
			$tmpItem = F0FModel::getTmpInstance('Items', 'ArsModel')->getTable();
			$tmpItem->load($id);

			if ($tmpItem->redirect_unauth)
			{
				$redirect = $tmpItem->redirect_unauth;
			}
			else
			{
				$release = F0FModel::getTmpInstance('Releases', 'ArsModel')->getTable();
				$release->load($tmpItem->release_id);

				// Do I have a redirect set on the release?
				if ($release->redirect_unauth)
				{
					$redirect = $release->redirect_unauth;
				}
				else
				{
					$category = F0FModel::getTmpInstance('Categories', 'ArsModel')->getTable();
					$category->load($release->category_id);

					if ($category->redirect_unauth)
					{
						$redirect = $category->redirect_unauth;
					}
				}
			}

			// Do I have a redirect set? If not, throw an error
			if ($redirect)
			{
				JFactory::getApplication()->redirect($redirect);
			}
			{
				$log->save(array('authorized' => 0));

				if ($params->get('banUnauth', 0))
				{
					// Let's fire the system plugin event. If Admin Tools is installed, it will handle this and ban the user
					$app->triggerEvent('onAdminToolsThirdpartyException', array(
							'ARSscraper',
							JText::_('COM_ARS_BLOCKED_MESSAGE'),
							array('Item : ' . $id)
						),
						true
					);
				}

				return JError::raiseError(403, JText::_('ACCESS FORBIDDEN'));
			}
		}

		$item->hit();
		$log->save(array(
				'item_id'    => $id,
				'authorized' => 1
			)
		);

		$model->doDownload();
		// No need to return anything; doDownload() calls the exit() method of the application object
	}
}