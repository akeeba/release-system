<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerCategory extends F0FController
{
	public function copy()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('core.create', 'com_ars')) {
			return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$item = $model->getItem();
		$key = $item->getKeyName();
		if($item->$key == $id)
		{
			$item->id = 0;
			$item->title = 'Copy of '.$item->title;
			$item->alias = 'copy-of-'.$item->alias;
			$item->ordering = 0;
			$item->created = '0000-00-00 00:00:00';
			$item->created_by = 0;
			$item->modified = '0000-00-00 00:00:00';
			$item->modified_by = 0;
			$item->checked_out_time = '0000-00-00 00:00:00';
			$item->checked_out = 0;
			$item->published = 0;
		}
		$status = $model->save($item);

		// redirect
		$option = $this->input->getCmd('option','com_ars');
		$view = $this->input->getCmd('view','category');
		$url = 'index.php?option='.$option.'&view='.$view;
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