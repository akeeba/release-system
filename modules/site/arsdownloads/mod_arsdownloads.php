<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
	if(!defined('FOF_INCLUDED')) JError::raiseError ('500', 'Your Akeeba Release System installation is broken; please re-install. Alternatively, extract the installation archive and copy the fof directory inside your site\'s libraries directory.');
}

if(!class_exists('MydownloadsModel')) {
	class MydownloadsModel
	{
		public function getItems($streams)
		{
			$items = array();
			
			$model = FOFModel::getTmpInstance('Updates','ArsModel');
			$dlmodel = FOFModel::getTmpInstance('Downloads','ArsModel');
			
			foreach($streams as $stream_id) {
				$model->getItems($stream_id);
				$temp = $model->items;
				if(empty($temp)) continue;
				
				$i = array_shift($temp);
				
				// Is the user authorized to download this item?
				$iFull = $dlmodel->getItem($i->item_id);
				if(is_null($iFull)) continue;
	
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