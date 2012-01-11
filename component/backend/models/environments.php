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
class ArsModelEnvironments extends ArsModelBase
{
	function buildQuery($overrideLimits = false) {
		$where = array();

		$fltSearch		= $this->getState('search', null, 'string');
		$fltXML			= $this->getState('xmltitle', null, 'string');

		$db = $this->getDBO();
		if($fltSearch) {
			$search = '%'.$fltSearch.'%';
			$where[] = '`title` LIKE '.$db->Quote($search);
		}
		if($fltXML) {
			$search = '%'.$fltXML.'%';
			$where[] = '`xmltitle` LIKE '.$db->Quote($search);
		}

		$query = 'SELECT * FROM `#__ars_environments`';

		if(count($where) && !$overrideLimits)
		{
			$query .= ' WHERE (' . implode(') AND (',$where) . ')';
		}

		if(!$overrideLimits) {
			$order = $this->getState('order',null,'cmd');
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