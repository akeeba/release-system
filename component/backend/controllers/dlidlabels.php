<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsControllerDlidlabels extends FOFController
{
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
}