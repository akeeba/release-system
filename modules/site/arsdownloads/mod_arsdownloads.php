<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

JModel::addIncludePath(JPATH_SITE.'/components/com_ars/models');
JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_ars/models');
JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_ars/tables');

$ars_backend = JPATH_SITE . DS . 'administrator' . DS . 'components' . DS . 'com_ars' . DS . 'models';
$ars_frontend = JPATH_SITE . DS . 'components' . DS . 'com_ars' . DS . 'models';

include_once($ars_backend.'/base.php');
include_once($ars_frontend.'/base.php');
include_once JPATH_ADMINISTRATOR.'/components/com_ars/tables/base.php';

if(!class_exists('MydownloadsModel')) {
	class MydownloadsModel
	{
		public function getItems($streams)
		{
			$items = array();
			
			$model = JModel::getInstance('Update','ArsModel');
			$dlmodel = JModel::getInstance('Download', 'ArsModel');
			
			foreach($streams as $stream_id) {
				$model->getItems($stream_id);
				$temp = $model->items;
				if(empty($temp)) continue;
				
				$i = array_shift($temp);
				
				// Is the user authorized to download this item?
				$iFull = $dlmodel->getItem($i->item_id);
				if(is_null($iFull)) continue;
				//if(empty($iFull->groups)) continue;
	
				// Add this item
				$newItem = array(
					'id'			=> $i->item_id,
					'release_id'	=> $i->release_id, 
					'name'			=> $i->name,
					'version'		=> $i->version,
					'maturity'		=> $i->maturity
				);
				$items[] = (object)$newItem;
			}
			
			return $items;
		}
	}
}

$streamsArray = array();
$streams = $params->get('streams','');

if(empty($streams)) return;

$temp = explode(',', $streams);
foreach($temp as $item)
{
	$streamsArray[] = (int)$item;
}

$model = new MydownloadsModel;
$items = $model->getItems($streamsArray);

if(!empty($items)){
	require JModuleHelper::getLayoutPath('mod_arsdownloads', $params->get('layout', 'default'));
}