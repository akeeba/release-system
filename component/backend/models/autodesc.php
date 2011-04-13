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
class ArsModelAutodesc extends ArsModelBase
{
	function  buildQuery($overrideLimits = false) {
		$where = array();

		$fltCategory	= $this->getState('category', null, 'int');
		$fltTitle		= $this->getState('title', null, 'string');
		$fltDescription	= $this->getState('description', null, 'string');
		$fltPublished	= $this->getState('published', null, 'cmd');

		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = '`category` ='.$db->getEscaped($fltCategory);
		}
		if($fltTitle) {
			$where[] = '`title` LIKE \'%'.$db->getEscaped($fltTitle).'\'';
		}
		if($fltDescription) {
			$where[] = '`description` LIKE \'%'.$db->getEscaped($fltDescription).'\'';
		}
		if($fltPublished != '') {
			$where[] = '`published` = '.$db->Quote((int)$fltPublished);
		}

		$query = <<<ENDSQL
SELECT
  `a`.*, `c`.`title` AS `cat_name`
FROM
  `#__ars_autoitemdesc` AS `a`
  LEFT OUTER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `a`.`category`)
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
}