<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelItems extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
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
				$db->qn('r').'.'.$db->qn('show_unauth_links').' AS '.$db->qn('rel_show_unauth'),

				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
				$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
				$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
				$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
				$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
				$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
				$db->qn('c').'.'.$db->qn('language').' AS '.$db->qn('cat_language'),
				$db->qn('c').'.'.$db->qn('show_unauth_links').' AS '.$db->qn('cat_show_unauth'),
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

		$fltItemId	= $this->getState('item_id', null, 'int');
		if($fltItemId > 0) {
			$query->where($db->qn('i').'.'.$db->qn('id').' = '.$db->q($fltItemId));
		}

		$fltRelease		= $this->getState('release', null, 'int');
		if($fltRelease > 0) {
			$query->where($db->qn('release_id').' = '.$db->q($fltRelease));
		}

		$fltAccess		= $this->getState('access', null, 'cmd');
		if($fltAccess) {
			$query->where($db->qn('i.access').' = '.$db->q($fltAccess));
		}

		$fltAccessUser	= $this->getState('access_user', null, 'int');
		if(!is_null($fltAccessUser))
		{
			$user = JFactory::getUser($fltAccessUser);

			if (!is_object($user) || !($user instanceof JUser))
			{
				$access_levels = array();
			}
			else
			{
				$access_levels = JFactory::getUser($fltAccessUser)->getAuthorisedViewLevels();
				$access_levels = array_map(array(JFactory::getDbo(), 'quote'), $access_levels);
			}

			$access_levels = array_unique($access_levels);

			$query->where($db->qn('c.access').' IN (' . implode(',', $access_levels) . ')');
			$query->where($db->qn('r.access').' IN (' . implode(',', $access_levels) . ')');

			// Davide 2013.09.13
			// If I want to display items to unauthorized users, I have not to filter them by access
			// $query->where($db->qn('i.access').' IN (' . implode(',', $access_levels) . ')');
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
		$fltLanguage2	= $this->getState('language2', null, 'string');
		if(($fltLanguage != '*') && ($fltLanguage != '')) {
			$query->where($db->qn('i').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
			$query->where($db->qn('r').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
			$query->where($db->qn('c').'.'.$db->qn('language').' IN ('.$db->q('*').','.$db->q($fltLanguage).')');
		} elseif($fltLanguage2) {
			$query->where($db->qn('i').'.'.$db->qn('language').' = '.$db->q($fltLanguage2));
			$query->where($db->qn('r').'.'.$db->qn('language').' = '.$db->q($fltLanguage2));
			$query->where($db->qn('c').'.'.$db->qn('language').' = '.$db->q($fltLanguage2));
		}

		$order = $this->getState('filter_order', 'ordering', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
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