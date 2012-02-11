<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.'/controllers/default.php';

class ArsControllerReleases extends ArsControllerDefault
{
	public function copy()
	{
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$user = JFactory::getUser();
			if (!$user->authorise('core.create', 'com_ars')) {
				return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			}
		}
		
		// Copy the release
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$item = $model->getItem();
		$key = $item->getKeyName();
		if($item->$key == $id)
		{
			$item->id = 0;
			$item->version = 'Copy of '.$item->version;
			$item->alias = 'copy-of-'.$item->alias;
			$item->ordering = 0;
			$item->created_by = 0;
			$item->created = '0000-00-00 00:00:00';
			$item->modified = '0000-00-00 00:00:00';
			$item->modified_by = 0;
			$item->checked_out_time = '0000-00-00 00:00:00';
			$item->checked_out = 0;
			$item->published = 0;
			$item->hits = 0;
		}
		$status = $model->save($item);
		$newRelease = $model->getSavedTable();

		// Get a list of contained items
		$itemModel = $this->getModel('Items','ArsModel');
		$itemModel->setState('release', $id);
		$temp = $itemModel->getItemList();

		jimport('joomla.utilities.date');
		$date = new JDate();
		
		foreach($temp as $item)
		{
			$item->id = 0;
			$item->release_id = $newRelease->id;
			$item->created = $date->toMySQL();
			if(!empty($item->environments)) {
				$item->environments = @json_decode($item->environments);
			}
			$item->modified = '0000-00-00 00:00:00';
			$item->modified_by = 0;
			$item->checked_out_time = '0000-00-00 00:00:00';
			$item->checked_out = 0;
			$item->published = 0;
			$item->hits = 0;

			$newStatus = $itemModel->save($item);
			$status = $status && $newStatus;
		}

		// redirect
		$option = JRequest::getCmd('option');
		$view = JRequest::getCmd('view');
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