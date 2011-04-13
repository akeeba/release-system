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

if(!JFile::exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'base.php')) {
	JError::raiseError(500,'Base Model not found');
	return false;
}

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'base.php';

class ArsModelUpdatestreams extends ArsModelBase
{
	function buildQuery($overrideLimits = false) {
		$where = array();

		$fltCategory	= $this->getState('type', null, 'cmd');
		$fltPublished	= $this->getState('published', null, 'cmd');

		$db = $this->getDBO();
		if($fltCategory) {
			$where[] = '`type` ='.$db->Quote($fltCategory);
		}
		if($fltPublished != '') {
			$where[] = '`published` = '.$db->Quote((int)$fltPublished);
		}

		$query = 'SELECT * FROM `#__ars_updatestreams`';

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