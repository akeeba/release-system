<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelItems extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->qn('i').'.*',
				$db->qn('r').'.'.$db->qn('category_id'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('alias').' AS '.$db->qn('rel_alias'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('r').'.'.$db->qn('groups').' AS '.$db->qn('rel_groups'),
				$db->qn('r').'.'.$db->qn('access').' AS '.$db->qn('rel_access'),
				$db->qn('r').'.'.$db->qn('published').' AS '.$db->qn('rel_published'),
				$db->qn('r').'.'.$db->qn('language').' AS '.$db->qn('rel_language'),
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
				$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
				$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
				$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
				$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
				$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
				$db->qn('c').'.'.$db->qn('language').' AS '.$db->qn('cat_language'),
			))
			->from($db->quoteName('#__ars_items').' AS '.$db->qn('i'))
			->join('INNER', $db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
				$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id').')')
			->join('INNER', $db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
				$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id').')')
			;
		
		
		$fltCategory	= $this->getState('category', null, 'int');
		if($fltCategory > 0) {
			$query->where($db->qn('r').'.'.$db->qn('category_id').' = '.$db->q($fltCategory));
		}
		
		$fltRelease		= $this->getState('release', null, 'int');
		if($fltRelease > 0) {
			$query->where($db->qn('release_id').' = '.$db->q($fltRelease));
		}
		
		$fltPublished	= $this->getState('published', null, 'cmd');
		if($fltPublished != '') {
			$query->where($db->qn('i').'.'.$db->qn('published').' = '.$db->q($fltPublished));
		}
		
		$fltFilename	= $this->getState('filename', null, 'string');
		if($fltFilename != '') {
			$query->where($db->qn('i').'.'.$db->qn('filename').' = '.$db->q($fltFilename));
		}
		
		$fltUrl			= $this->getState('url', null, 'string');
		if($fltUrl != '') {
			$query->where($db->qn('i').'.'.$db->qn('url').' = '.$db->q($fltUrl));
		}
		
		$fltLanguage	= $this->getState('language', null, 'cmd');
		if($fltLanguage != '') {
			$query->where($db->qn('i').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
			$query->where($db->qn('r').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
			$query->where($db->qn('c').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
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
		$fltRelease		= $this->getState('release', null, 'int');
		$fltPublished	= $this->getState('published', null, 'cmd');
		
		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = $db->qn('category_id').' = '.$db->q($fltCategory);
		}
		if($fltRelease) {
			$where[] = $db->qn('release_id').' = '.$db->q($fltCategory);
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