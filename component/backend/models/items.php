<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

if(!class_exists('ArsModelBase'))
{
	if(!JFile::exists(JPATH_COMPONENT_ADMINISTRATOR.'/models/base.php')) {
		JError::raiseError(500,'Base Model not found');
		return false;
	}
	require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/base.php';
}
class ArsModelItems extends ArsModelBase
{
	public function save($data) {
		// When the user unselects all group checkboxes, the groups key is not
		// set, causing the model to never reset them to "none selected"
		if(!array_key_exists('groups', $data)) {
			$data['groups'] = '';
		}
		return parent::save($data);
	}
	
	function  buildQuery($overrideLimits = false) {
		$where = array();

		$fltCategory	= $this->getState('category', null, 'int');
		$fltRelease		= $this->getState('release', null, 'int');
		$fltPublished	= $this->getState('published', null, 'cmd');
		$fltFilename	= $this->getState('filename', null, 'string');
		$fltUrl			= $this->getState('url', null, 'string');
		$fltLanguage	= $this->getState('language', null, 'cmd');

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
		if($fltLanguage) {
			$where[] = '`i`.`language` IN ("*", '.$db->quote($fltLanguage).')';
			$where[] = '`r`.`cat_language` IN ("*", '.$db->quote($fltLanguage).')';
			$where[] = '`r`.`language` IN ("*", '.$db->quote($fltLanguage).')';
		}

		$query = <<<ENDSQL
SELECT
    `i`.*,
    `r`.`category_id`, `r`.`version`, `r`.`alias` as `rel_alias`,
    `maturity`, `r`.`groups` as `rel_groups`, `r`.`access` as `rel_access`,
    `r`.`published` as `rel_published`, `r`.`language` as `rel_language`,
    `cat_title`, `cat_alias`, `cat_type`, `cat_groups`,
    `cat_directory`, `cat_access`, `cat_published`, `cat_language`
FROM
    `#__ars_items` as `i`
    INNER JOIN (
	SELECT
	    `r`.*, `c`.`title` as `cat_title`, `c`.`alias` as `cat_alias`,
	    `c`.`type` as `cat_type`, `c`.`groups` as `cat_groups`,
	    `c`.`directory` as `cat_directory`, `c`.`access` as `cat_access`,
	    `c`.`published` as `cat_published`, `c`.`language` as `cat_language`
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