<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class ArsControllerRelease extends FOFController
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
			$item->version = 'Copy of '.$item->version;
			$item->alias = 'copy-of-'.$item->alias;
			$item->ordering = 0;
			$item->created = '0000-00-00 00:00:00';
			$item->created_by = 0;
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
		$temp = FOFModel::getTmpInstance('Items','ArsModel')
			->release($id)
			->getItemList();

		JLoader::import('joomla.utilities.date');
		$date = new JDate();

		if(!empty($temp)) foreach($temp as $item)
		{
			$item->id = 0;
			$item->release_id = $newRelease->id;
			$item->created = $date->toSql();
			if(!empty($item->environments)) {
				$item->environments = @json_decode($item->environments);
			}
			$item->modified = '0000-00-00 00:00:00';
			$item->modified_by = 0;
			$item->checked_out_time = '0000-00-00 00:00:00';
			$item->checked_out = 0;
			$item->published = 0;
			$item->hits = 0;

			$table = FOFModel::getTmpInstance('Items','ArsModel')->getTable();
			$table->reset();
			$newStatus = $table->save($item);
			$status = $status && $newStatus;
		}

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