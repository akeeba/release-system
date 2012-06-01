<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelEnvironments extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->qn('#__ars_environments'));
		
		$fltSearch		= $this->getState('search', null, 'string');
		if($fltSearch) {
			$fltSearch = "%$fltSearch%";
			$query->where($db->qn('title').' LIKE '.$db->q($fltSearch));
		}
		
		$fltXML			= $this->getState('xmltitle', null, 'string');
		if($fltXML) {
			$fltXML = "%$fltXML%";
			$query->where($db->qn('xmltitle').' LIKE '.$db->q($fltXML));
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}