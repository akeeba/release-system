<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelAutodescs extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->qn('a').'.*',
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_name'),
			))
			->from($db->qn('#__ars_autoitemdesc').' AS '.$db->qn('a'))
			->join('INNER', $db->qn('#__ars_categories').' AS '.$db->qn('c').'ON('.
					$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('a').'.'.$db->qn('category').')')	
			;
		
		$fltCategory	= $this->getState('title', null, 'int');
		if($fltCategory) {
			$query->where($db->qn('category').' = '.$db->q($fltCategory));
		}
		
		$fltTitle		= $this->getState('title', null, 'string');
		if($fltTitle) {
			$fltTitle = "%$fltTitle%";
			$query->where($db->qn('title').' LIKE '.$db->q($fltTitle));
		}
		
		$fltDescription	= $this->getState('description', null, 'string');
		if($fltDescription) {
			$fltDescription = "%$fltDescription%";
			$query->where($db->qn('description').' LIKE '.$db->q($fltDescription));
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