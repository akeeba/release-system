<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelVgroups extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->qn('#__ars_vgroups'));
		
		$fltTitle	= $this->getState('title', null, 'string');
		if($fltTitle) {
			$query->where($db->qn('title').' LIKE '.$db->q("%$fltTitle%"));
		}
		
		$fltPublished	= $this->getState('published', null, 'cmd');
		if($fltPublished != '') {
			$query->where($db->qn('published').' = '.$db->q($fltPublished));
		}
		
		$fltFrontend	= $this->getState('frontend', 0, 'int');
		if($fltFrontend != 0) {
			$order = 'ordering';
			$dir = 'ASC';
		} else {
			$order = $this->getState('filter_order', 'id', 'cmd');
			if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
			$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		}
		
		$query->order($order.' '.$dir);

		return $query;
	}
}