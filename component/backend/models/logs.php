<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

if(!class_exists('ArsModelBase'))
{
	if(!JFile::exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'base.php')) {
		JError::raiseError(500,'Base Model not found');
		return false;
	}
	require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'base.php';
}

class ArsModelLogs extends ArsModelBase
{
	function  buildQuery($overrideLimits = false) {
		$where = array();

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

		$db = $this->getDBO();
		if($fltItemText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$itemIDs = $this->getItems($fltItemText);
			if(empty($itemIDs)) {
				$where[] = 'FALSE';
			} else {
				$ids = implode(',', $itemIDs);
				$where[] = 'item_id IN ('.$ids.')';
			}
		}
		if($fltUserText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$userIDs = $this->getUsers($fltUserText);
			if(empty($userIDs)) {
				$where[] = 'FALSE';
			} else {
				$ids = implode(',', $userIDs);
				$where[] = 'user_id IN ('.$ids.')';
			}
		}
		if($fltReferer) {
			$where[] = '`referer` LIKE "%'.$db->getEscaped($fltReferer).'%"';
		}
		if($fltIP) {
			$where[] = '`ip` LIKE "%'.$db->getEscaped($fltIP).'%"';
		}
		if($fltCountry) {
			$where[] = '`country` = '.$db->Quote($fltCountry);
		}
		if(is_numeric($fltAuthorized)) {
			$where[] = '`authorized` = '.$db->Quote($fltAuthorized);
		}
		if($fltCategory) {
			$query_inner = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_releases').' WHERE '.
				$db->nameQuote('category_id').' = '.$db->quote($fltCategory);
			$query_outer = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_items').' WHERE '.
				$db->nameQuote('release_id').' IN ('.$query_inner.')';
			$db->setQuery($query_outer);
			$ids = $db->loadResultArray();
			$clause = '('.implode(", ", $ids).')';
			
			$where[] = '`item_id` IN '.$clause;
		}
		if($fltVersion) {
			$query_outer = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_items').' WHERE '.
				$db->nameQuote('release_id').' = '.$db->quote($fltVersion);
			$db->setQuery($query_outer);
			$ids = $db->loadResultArray();
			$clause = '('.implode(", ", $ids).')';
			
			$where[] = '`item_id` IN '.$clause;
		}

		$query = <<<ENDSQL
SELECT
  l.*,
  c.title as category, r.version, r.maturity, i.title as item,
  IF(i.`type` = 'file', i.filename, i.url) as asset, i.updatestream, i.filesize,
  i.release_id, r.category_id,
  u.name, u.username, u.email
FROM
  #__ars_log AS l
  JOIN #__ars_items AS i ON(i.id = l.item_id)
  JOIN #__ars_releases AS r ON(r.id = i.release_id)
  JOIN #__ars_categories AS c ON(c.id = r.category_id)
  LEFT JOIN #__users AS u ON(u.id = user_id)
ENDSQL;

		if(count($where) && !$overrideLimits)
		{
			$query .= ' WHERE (' . implode(') AND (',$where) . ')';
		}

		if(!$overrideLimits) {
			$order = $this->getState('order',null,'cmd');
			if($order === 'Array') $order = null;
			$dir = $this->getState('dir',null,'cmd');

			$app = JFactory::getApplication();
			$hash = $this->getHash();
			if(empty($order)) {
				$order = $app->getUserStateFromRequest($hash.'filter_order', 'filter_order', 'id');
			}
			if(empty($dir)) {
				$dir = $app->getUserStateFromRequest($hash.'filter_order_Dir', 'filter_order_Dir', 'DESC');
				$dir = in_array(strtoupper($dir),array('DESC','ASC')) ? strtoupper($dir) : "ASC";
			}

			$query .= ' ORDER BY '.$db->nameQuote($order).' '.$dir;
		}
		return $query;
	}
	
	function  buildCountQuery() {
		$where = array();

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

		$db = $this->getDBO();
		/**/
		if($fltItemText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$itemIDs = $this->getItems($fltItemText);
			if(empty($itemIDs)) {
				$where[] = 'FALSE';
			} else {
				$ids = implode(',', $itemIDs);
				$where[] = 'item_id IN ('.$ids.')';
			}
		}
		if($fltUserText) {
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$userIDs = $this->getUsers($fltUserText);
			if(empty($userIDs)) {
				$where[] = 'FALSE';
			} else {
				$ids = implode(',', $userIDs);
				$where[] = 'user_id IN ('.$ids.')';
			}
		}
		/**/
		if($fltReferer) {
			$where[] = '`referer` LIKE "%'.$db->getEscaped($fltReferer).'%"';
		}
		if($fltIP) {
			$where[] = '`ip` LIKE "%'.$db->getEscaped($fltIP).'%"';
		}
		if($fltCountry) {
			$where[] = '`country` LIKE "%'.$db->getEscaped($fltCountry).'%"';
		}
		if(is_numeric($fltAuthorized)) {
			$where[] = '`authorized` = '.$db->Quote($fltAuthorized);
		}
		if($fltCategory) {
			$query_inner = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_releases').' WHERE '.
				$db->nameQuote('category_id').' = '.$db->quote($fltCategory);
			$query_outer = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_items').' WHERE '.
				$db->nameQuote('release_id').' IN ('.$query_inner.')';
			$db->setQuery($query_outer);
			$ids = $db->loadResultArray();
			$clause = '('.implode(", ", $ids).')';
			
			$where[] = '`item_id` IN '.$clause;
		}
		if($fltVersion) {
			$query_outer = 'SELECT '.$db->nameQuote('id').' FROM '.$db->nameQuote('#__ars_items').' WHERE '.
				$db->nameQuote('release_id').' = '.$db->quote($fltVersion);
			$db->setQuery($query_outer);
			$ids = $db->loadResultArray();
			$clause = '('.implode(", ", $ids).')';
			
			$where[] = '`item_id` IN '.$clause;
		}

		$query = <<<ENDSQL
SELECT
  COUNT(*)
FROM
  #__ars_log AS l
ENDSQL;

		if(count($where))
		{
			$query .= ' WHERE (' . implode(') AND (',$where) . ')';
		}

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
		$qfrag = "'%" . $db->getEscaped($frag) . "%'";
		$db->setQuery("SELECT `id` FROM `#__users` WHERE `name` LIKE $qfrag OR `username` LIKE $qfrag OR `email` LIKE $qfrag OR `params` LIKE $qfrag");
		return $db->loadResultArray();
	}
	
	/**
	 * Gets a list of download item IDs whose title contains the $frag string
	 * @param string $frag
	 * @return array|null
	 */
	private function getItems($frag)
	{
		$db = $this->getDBO();
		$qfrag = "'%" . $db->getEscaped($frag) . "%'";
		$query = <<<ENDQUERY
select
	id
FROM
	`#__ars_items`
WHERE
	title LIKE $qfrag
ENDQUERY;
		$db->setQuery($query);
		return $db->loadResultArray();
	}
}