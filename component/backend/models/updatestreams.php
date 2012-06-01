<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelUpdatestreams extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->qn('#__ars_updatestreams'));
		
		$fltCategory	= $this->getState('type', null, 'int');
		if($fltCategory) {
			$query->where($db->qn('type').' = '.$db->q($fltCategory));
		}
		
		$fltPublished	= $this->getState('published', null, 'cmd');
		if($fltPublished != '') {
			$query->where($db->qn('published').' = '.$db->q($fltPublished));
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}