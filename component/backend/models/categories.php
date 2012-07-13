<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelCategories extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->quoteName('#__ars_categories'));
		
		$fltTitle		= $this->getState('title', null, 'string');
		if($fltTitle) {
			$fltTitle = "%$fltTitle%";
			$query->where($db->qn('title').' LIKE '.$db->q($fltTitle));
		}
		
		$fltAlias		= $this->getState('alias', null, 'string');
		if($fltAlias) {
			$query->where($db->qn('alias').' = '.$db->q($fltAlias));
		}
		
		$fltDescription	= $this->getState('description', null, 'string');
		if($fltDescription) {
			$fltDescription = "%$fltDescription%";
			$query->where($db->qn('description').' LIKE '.$db->q($fltDescription));
		}
		
		$fltVgroup		= $this->getState('vgroup', null, 'int');
		if($fltVgroup) {
			$query->where($db->qn('vgroup_id').' = '.$db->q($fltAlias));
		}
		
		$fltType		= $this->getState('type', null, 'cmd');
		if($fltType) {
			$query->where($db->qn('type').' = '.$db->q($fltType));
		}
		
		$fltAccess		= $this->getState('access', null, 'cmd');
		if($fltAccess) {
			$query->where($db->qn('access').' = '.$db->q($fltAccess));
		}
		
		$fltPublished	= $this->getState('published', null, 'cmd');
		if($fltPublished != '') {
			$query->where($db->qn('published').' = '.$db->q($fltPublished));
		}
		
		$fltNoBEUnpub	= $this->getState('nobeunpub', null, 'int');
		if($fltNoBEUnpub) {
			$query->where('NOT('.$db->qn('published').' = '.$db->q('0').' AND '.
					$db->qn('type').'='.$db->q('bleedingedge').')');
		}
		
		$fltLanguage	= $this->getState('language', null, 'cmd');
		if($fltLanguage) {
			$query->where($db->qn('language').' IN('.$db->q('*').','.$db->q($fltLanguage).')');
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->qn('title').' LIKE '.$db->quote($search).') OR'.
				'('.$db->qn('description').' LIKE '.$db->quote($search).')'.
				')'
			);
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}