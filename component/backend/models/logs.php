<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelLogs extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->qn('l').'.*',
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('category'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('i').'.'.$db->qn('title').' AS '.$db->qn('item'),
				'IF('.$db->qn('i').'.'.$db->qn('type').' = '.$db->q('file').','.$db->qn('i').'.'.$db->qn('filename').','.$db->qn('i').'.'.$db->qn('url').') AS '.$db->qn('asset'),
				$db->qn('i').'.'.$db->qn('updatestream'),
				$db->qn('i').'.'.$db->qn('filesize'),
				$db->qn('i').'.'.$db->qn('release_id'),
				$db->qn('r').'.'.$db->qn('category_id'),
				$db->qn('u').'.'.$db->qn('name'),
				$db->qn('u').'.'.$db->qn('username'),
				$db->qn('u').'.'.$db->qn('email')
			))
			->from($db->qn('#__ars_log').' AS '.$db->qn('l'))
			->join('INNER', $db->qn('#__ars_items').' AS '.$db->qn('i').' ON('.$db->qn('i').'.'.$db->qn('id').' = '.$db->qn('l').'.'.$db->qn('item_id').')')
			->join('INNER', $db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id').')')
			->join('INNER', $db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id').')')
			->join('LEFT', $db->qn('#__users').' AS '.$db->qn('u').' ON('.$db->qn('u').'.'.$db->qn('id').' = '.$db->qn('user_id').')')
			;
		
		$fltItemText	= $this->getState('itemtext', null, 'string');
		$fltUserText	= $this->getState('usertext', null, 'string');
		$fltReferer		= $this->getState('referer', null, 'string');
		$fltIP			= $this->getState('ip', null, 'string');
		$fltCountry		= $this->getState('country', null, 'string');
		$fltAuthorized	= $this->getState('authorized', null, 'cmd');
		$fltCategory	= $this->getState('category', null, 'int');
		$fltVersion		= $this->getState('version', null, 'int');
		
		if(!is_null($fltAuthorized) && ($fltAuthorized != '')) {
			$fltAuthorized = (int)$fltAuthorized;
		} else {
			$fltAuthorized = null;
		}
		
		if($fltItemText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$itemIDs = $this->getItems($fltItemText);
			if(empty($itemIDs)) {
				$query->where('FALSE');
			} else {
				$ids = implode(',', $itemIDs);
				$query->where($db->qn('item_id').' IN('.$ids.')');
			}
		}
		if($fltUserText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$userIDs = $this->getUsers($fltUserText);
			if(empty($userIDs)) {
				$query->where('FALSE');
			} else {
				$ids = implode(',', $userIDs);
				$query->where($db->qn('user_id').' IN('.$ids.')');
			}
		}
		if($fltReferer) {
			$query->where($db->qn('referer').' LIKE '.$db->q("%$fltReferer%"));
		}
		if($fltIP) {
			$query->where($db->qn('ip').' LIKE '.$db->q("%$fltIP%"));
		}
		if($fltCountry) {
			$query->where($db->qn('country').' = '.$db->q($fltCountry));
		}
		if(is_numeric($fltAuthorized)) {
			$query->where($db->qn('authorized').' = '.$db->q($fltAuthorized));
		}
		if($fltCategory) {
			$query_inner = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_releases'))
				->where($db->qn('category_id').' = '.$db->q($fltCategory));
			$query_outer = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_items'))
				->where($db->qn('release_id').' IN ('.$query_inner.')');
			$db->setQuery($query_outer);
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$ids = $db->loadColumn();
			} else {
				$ids = $db->loadResultArray();
			}
			$clause = '('.implode(", ", $ids).')';
			
			$query->where($db->qn('item_id').' IN '.$clause);
		}
		
		if($fltVersion) {
			$query_outer = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_items'))
				->where($db->qn('release_id').' = '.$db->q($fltVersion));
			$db->setQuery($query_outer);
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$ids = $db->loadColumn();
			} else {
				$ids = $db->loadResultArray();
			}
			$clause = '('.implode(", ", $ids).')';
			
			$query->where($db->qn('item_id').' IN '.$clause);
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
	
	public function buildCountQuery() {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('COUNT(*)')
			->from($db->qn('#__ars_log').' AS '.$db->qn('l'))
			;
		
		$fltItemText	= $this->getState('itemtext', null, 'string');
		$fltUserText	= $this->getState('usertext', null, 'string');
		$fltReferer		= $this->getState('referer', null, 'string');
		$fltIP			= $this->getState('ip', null, 'string');
		$fltCountry		= $this->getState('country', null, 'string');
		$fltAuthorized	= $this->getState('authorized', null, 'cmd');
		$fltCategory	= $this->getState('category', null, 'int');
		$fltVersion		= $this->getState('version', null, 'int');
		
		if(!is_null($fltAuthorized) && ($fltAuthorized != '')) {
			$fltAuthorized = (int)$fltAuthorized;
		} else {
			$fltAuthorized = null;
		}
		
		if($fltItemText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$itemIDs = $this->getItems($fltItemText);
			if(empty($itemIDs)) {
				$query->where('FALSE');
			} else {
				$ids = implode(',', $itemIDs);
				$query->where($db->qn('item_id').' IN('.$ids.')');
			}
		}
		if($fltUserText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$userIDs = $this->getUsers($fltUserText);
			if(empty($userIDs)) {
				$query->where('FALSE');
			} else {
				$ids = implode(',', $userIDs);
				$query->where($db->qn('user_id').' IN('.$ids.')');
			}
		}
		if($fltReferer) {
			$query->where($db->qn('referer').' LIKE '.$db->q("%$fltReferer%"));
		}
		if($fltIP) {
			$query->where($db->qn('ip').' LIKE '.$db->q("%$fltIP%"));
		}
		if($fltCountry) {
			$query->where($db->qn('country').' = '.$db->q($fltCountry));
		}
		if(is_numeric($fltAuthorized)) {
			$query->where($db->qn('authorized').' = '.$db->q($fltAuthorized));
		}
		if($fltCategory) {
			$query_inner = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_releases'))
				->where($db->qn('category_id').' = '.$db->q($fltCategory));
			$query_outer = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_items'))
				->where($db->qn('release_id').' IN ('.$query_inner.')');
			$db->setQuery($query_outer);
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$ids = $db->loadColumn();
			} else {
				$ids = $db->loadResultArray();
			}
			$clause = '('.implode(", ", $ids).')';
			
			$query->where($db->qn('item_id').' IN '.$clause);
		}
		
		if($fltVersion) {
			$query_outer = FOFQueryAbstract::getNew($db)
				->select($db->qn('id'))
				->from($db->qn('#__ars_items'))
				->where($db->qn('release_id').' = '.$db->q($fltVersion));
			$db->setQuery($query_outer);
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$ids = $db->loadColumn();
			} else {
				$ids = $db->loadResultArray();
			}
			$clause = '('.implode(", ", $ids).')';
			
			$query->where($db->qn('item_id').' IN '.$clause);
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
	
	/**
	 * Returns the user IDs whose username, email address or real name contains the $frag string
	 * @param string $frag
	 * @return array|null
	 */
	private function getUsers($frag)
	{
		$db = $this->getDBO();
		
		$qfrag = $db->q("%".$frag."%");
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__users'))
			->where($db->qn('name').' LIKE '.$qfrag, 'OR')
			->where($db->qn('username').' LIKE '.$qfrag, 'OR')
			->where($db->qn('email').' LIKE '.$qfrag, 'OR')
			->where($db->qn('params').' LIKE '.$qfrag, 'OR');
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			return $db->loadColumn();
		} else {
			return $db->loadResultArray();
		}
	}
	
	/**
	 * Gets a list of download item IDs whose title contains the $frag string
	 * @param string $frag
	 * @return array|null
	 */
	private function getItems($frag)
	{
		$db = $this->getDBO();
		$qfrag = $db->q("%".$frag."%");
		$query = FOFQueryAbstract::getNew($db)
			->select($db->qn('id'))
			->from($db->qn('#__ars_items'))
			->where($db->qn('title').' LIKE '.$qfrag);
		
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			return $db->loadColumn();
		} else {
			return $db->loadResultArray();
		}
	}	
}