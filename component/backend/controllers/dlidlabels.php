<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDlidlabels extends F0FController
{
	/**
	 * Executes a controller task. Overriden to make sure non-logged-in users
	 * cannot create add-on Download IDs.
	 *
	 * @param   string $task The task to execute.
	 *
	 * @return  null|bool  False on execution failure
	 *
	 * @throws  Exception
	 */
	public function execute($task)
	{
		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$id = JFactory::getUser()->id;
			if ($id <= 0)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}
			$this->input->set('user_id', $id);
		}

		parent::execute($task);
	}

	public function reset()
	{
		// CSRF prevention
		if ($this->csrfProtection)
		{
			$this->_csrfProtection();
		}

		$model = $this->getThisModel();

		if (!$model->getId())
		{
			$model->setIDsFromRequest();
		}

		$status = $model->resetDownloadId();

		// Redirect
		if ($customURL = $this->input->get('returnurl', '', 'string'))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=' . $this->component . '&view=' . F0FInflector::pluralize($this->view) . $this->getItemidURLSuffix();

		if (!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}

		return $status;
	}

	protected function onBeforeBrowse()
	{
		$result = parent::onBeforeBrowse();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if ($result && !$isAdmin && !$isCLI)
		{
			$result = !JFactory::getUser()->guest;
		}

		return $result;
	}

	protected function onBeforeAdd()
	{
		$result = parent::onBeforeAdd();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$result = !JFactory::getUser()->guest;
			$this->layout = 'form';
		}

		return $result;
	}

	/**
	 * Edit view permissions check. Overriden to make sure a user won't try
	 * editing another user's add-on Download IDs.
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	protected function onBeforeEdit()
	{
		$result = parent::onBeforeEdit();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (($result !== false) && !$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();

			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			$item = $model->getItem();

			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}

			if ($item->primary)
			{
				throw new Exception(JText::_('COM_ARS_DLIDLABELS_ERR_CANTEDITDEFAULT'), 403);

				return false;
			}

			$this->layout = 'form';
		}

		return ($result !== false);
	}

	/**
	 * Edit view permissions check. Overriden to make sure a user won't try
	 * editing another user's add-on Download IDs.
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	protected function onBeforeReset()
	{
		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();

			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			$item = $model->getItem();

			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}

			$this->layout = 'form';
		}

		return true;
	}

	/**
	 * Read view permissions check. Overriden to make sure a user won't try
	 * reading another user's add-on Download IDs.
	 *
	 * @return boolean
	 * @throws Exception
	 */
	protected function onBeforeRead()
	{
		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();
			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			$item = $model->getItem();
			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}
		}

		return true;
	}

	protected function onBeforeCancel()
	{
		return !JFactory::getUser()->guest;
	}

	protected function onBeforeSave()
	{
		$result = parent::onBeforeSave();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (($result !== false) && !$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();
			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			$item = $model->getItem();
			if (!JFactory::getUser()->guest && ($model->getId() == 0))
			{
				$result = true;
			}
			elseif ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}
		}

		return ($result !== false);
	}

	protected function onBeforePublish()
	{
		$result = parent::onBeforePublish();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();

			// I don't have an id let's fetch id from the request
			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			// Ehm... no id? it means that the user really selected nothing
			if(!$model->getId())
			{
				$msg = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
				JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_ars&view=dlidlabels'), $msg, 'notice');
			}

			$item = $model->getItem();

			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}

			$result = true;
		}

		return $result;
	}

	protected function onAfterPublish()
	{
		// After touching a Download ID I have to clear the cache, otherwise I won't see the changes
		F0FUtilsCacheCleaner::clearCacheGroups(array('com_ars'));

		return true;
	}

	protected function onBeforeUnpublish()
	{
		$result = parent::onBeforeUnpublish();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();

			// I don't have an id let's fetch id from the request
			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			// Ehm... no id? it means that the user really selected nothing
			if(!$model->getId())
			{
				$msg = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
				JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_ars&view=dlidlabels'), $msg, 'notice');
			}

			$item = $model->getItem();

			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}

			$result = true;
		}

		return $result;
	}

	protected function onAfterUnpublish()
	{
		// After touching a Download ID I have to clear the cache, otherwise I won't see the changes
		F0FUtilsCacheCleaner::clearCacheGroups(array('com_ars'));

		return true;
	}

	protected function onBeforeRemove()
	{
		$result = parent::onBeforeRemove();

		list($isCLI, $isAdmin) = F0FDispatcher::isCliAdmin();

		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();

			// I don't have an id let's fetch id from the request
			if (!$model->getId())
			{
				$model->setIDsFromRequest();
			}

			// Ehm... no id? it means that the user really selected nothing
			if(!$model->getId())
			{
				$msg = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
				JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_ars&view=dlidlabels'), $msg, 'notice');
			}

			$item = $model->getItem();

			if ($item->user_id != JFactory::getUser()->id)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);

				return false;
			}

			$result = true;
		}

		return $result;
	}
}