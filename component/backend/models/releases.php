<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelReleases extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->qn('r').'.*',
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
				$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
				$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
				$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
				$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
				$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
				$db->qn('c').'.'.$db->qn('language').' AS '.$db->qn('cat_language'),
			))
			->from($db->quoteName('#__ars_releases').' AS '.$db->qn('r'))
			->join('INNER', $db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
				$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id').')')
			;
		
		
		$fltCategory	= $this->getState('category', null, 'int');
		if($fltCategory > 0) {
			$query->where($db->qn('category_id').' = '.$db->q($fltCategory));
		}
		
		$fltVersion		= $this->getState('version', null, 'string');
		if($fltVersion) {
			$query->where($db->qn('version').' = '.$db->q($fltVersion));
		}
		
		$fltPublished	= $this->getState('published', null, 'cmd');
		if($fltPublished != '') {
			$query->where($db->qn('r').'.'.$db->qn('published').' = '.$db->q($fltPublished));
		}
		
		$fltNoBEUnpub	= $this->getState('nobeunpub', null, 'int');
		if($fltNoBEUnpub) {
			$query->where('NOT('.$db->qn('r').'.'.$db->qn('published').' = '.$db->q('0').' AND '.
					$db->qn('c').'.'.$db->qn('type').'='.$db->q('bleedingedge').')');
		}
		
		$fltLanguage	= $this->getState('language', null, 'cmd');
		if($fltLanguage != '') {
			$query->where($db->qn('r').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
			$query->where($db->qn('c').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
		}
		
		$fltMaturity	= $this->getState('maturity', 'alpha', 'cmd');
		switch($fltMaturity) {
			case 'beta':
				$query->where($db->qn('r').'.'.$db->qn('maturity').' IN ('.$db->q('beta'),','.$db->q('rc').','.$db->q('stable').')');
				break;
			case 'rc':
				$query->where($db->qn('r').'.'.$db->qn('maturity').' IN ('.$db->q('rc').','.$db->q('stable').')');
				break;
			case 'stable':
				$query->where($db->qn('r').'.'.$db->qn('maturity').' = '.$db->q('stable'));
				break;
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
	
	function getReorderWhere()
	{
		$where = array();
		$fltCategory	= $this->getState('category', null, 'int');
		$fltPublished	= $this->getState('published', null, 'cmd');
		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = $db->qn('category_id').' = '.$db->q($fltCategory);
		}
		if($fltPublished != '') {
			$where[] = $db->qn('published').' = '.$db->q($fltPublished);
		}
		if(count($where)) {
			return '(' . implode(') AND (',$where) . ')';
		} else {
			return '';
		}
	}
}