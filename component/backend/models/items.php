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
class ArsModelItems extends ArsModelBase
{
	function  buildQuery($overrideLimits = false) {
		$where = array();

		$fltCategory	= $this->getState('category', null, 'int');
		$fltRelease		= $this->getState('release', null, 'int');
		$fltPublished	= $this->getState('published', null, 'cmd');
		$fltFilename	= $this->getState('filename', null, 'string');
		$fltUrl			= $this->getState('url', null, 'string');

		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = '`category_id` ='.$db->getEscaped($fltCategory);
		}
		if($fltRelease) {
			$where[] = '`release_id` ='.$db->getEscaped($fltRelease);
		}
		if($fltPublished != '') {
			$where[] = '`i`.`published` = '.$db->Quote((int)$fltPublished);
		}
		if(!empty($fltFilename)) {
			$where[] = '`filename` = '.$db->Quote($fltFilename);
		}
		if(!empty($fltUrl)) {
			$where[] = '`url` = '.$db->Quote($fltUrl);
		}

		$query = <<<ENDSQL
SELECT
    `i`.*,
    `r`.`category_id`, `r`.`version`, `r`.`alias` as `rel_alias`,
    `maturity`, `r`.`groups` as `rel_groups`, `r`.`access` as `rel_access`,
    `r`.`published` as `rel_published`,
    `cat_title`, `cat_alias`, `cat_type`, `cat_groups`,
    `cat_directory`, `cat_access`, `cat_published`
FROM
    `#__ars_items` as `i`
    INNER JOIN (
	SELECT
	    `r`.*, `c`.`title` as `cat_title`, `c`.`alias` as `cat_alias`,
	    `c`.`type` as `cat_type`, `c`.`groups` as `cat_groups`,
	    `c`.`directory` as `cat_directory`, `c`.`access` as `cat_access`,
	    `c`.`published` as `cat_published`
	FROM
	    `#__ars_releases` AS `r`
	    INNER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `r`.`category_id`)
    ) AS `r` ON(`r`.`id` = `i`.`release_id`)
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


	function getReorderWhere()
	{
		$where = array();

		$fltCategory	= $this->getState('category', null, 'int');
		$fltRelease		= $this->getState('release', null, 'int');
		$fltPublished	= $this->getState('published', null, 'cmd');

		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = '`category_id` ='.$db->getEscaped($fltCategory);
		}
		if($fltRelease) {
			$where[] = '`release_id` ='.$db->getEscaped($fltRelease);
		}
		if($fltPublished != '') {
			$where[] = '`published` = '.$db->Quote((int)$fltPublished);
		}

		if(count($where)) {
			return '(' . implode(') AND (',$where) . ')';
		} else {
			return '';
		}
	}
}