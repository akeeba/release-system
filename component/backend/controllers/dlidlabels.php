<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDlidlabels extends FOFController
{
	/**
	 * Executes a controller task. Overriden to make sure non-logged-in users
	 * cannot create add-on Download IDs.
	 * 
	 * @param   string  $task  The task to execute.
	 * 
	 * @throws  Exception
	 */
	public function execute($task) {
		list($isCLI, $isAdmin) = FOFDispatcher::isCliAdmin();
		
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
	
	/**
	 * Edit view permissions check. Overriden to make sure a user won't try
	 * editing another user's add-on Download IDs.
	 * 
	 * @return boolean
	 * 
	 * @throws Exception
	 */
	protected function onBeforeEdit() {
		$result = parent::onBeforeEdit();
		
		list($isCLI, $isAdmin) = FOFDispatcher::isCliAdmin();
		
		if (($result !== false) && !$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();
			if(!$model->getId())
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
		
		return ($result !== false);
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
		list($isCLI, $isAdmin) = FOFDispatcher::isCliAdmin();
		
		if (!$isAdmin && !$isCLI)
		{
			$model = $this->getThisModel();
			if(!$model->getId())
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
	
	protected function onBeforeApplySave(&$data) {
		$result = parent::onBeforeApplySave($data);
		
		list($isCLI, $isAdmin) = FOFDispatcher::isCliAdmin();
		if (($result !== false) && !$isAdmin && !$isCLI)
		{
			if (JFactory::getUser()->guest)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
				return false;
			}

			$result = true;
			
			$user_id = JFactory::getUser()->id;
			$myData = (array)$data;
			if (isset($data['user_id']))
			{
				if ($data['user_id'] != $user_id)
				{
					throw new Exception(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
					return false;
				}
			}
		}
		
		return $result;
	}
}