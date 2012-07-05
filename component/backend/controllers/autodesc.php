<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerAutodesc extends FOFController
{
	public function copy()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$ids = $model->getIds();
		
		$status = true;
		if(!empty($ids)) foreach($ids as $id) {
			$model->setId($id);
			$item = $model->getItem();
			
			if($item->id == $id)
			{
				$item->title = 'Copy of '.$item->title;
				$item->id = 0;
			}
			$status = $model->save($item);
			if(!$status) break;
		}

		// redirect
		$option = FOFInput::getCmd('option','com_ars',$this->input);
		$view = FOFInput::getCmd('view','autodescs',$this->input);
		$url = 'index.php?option='.$option.'&view='.$view.'&task=browse';
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}
}