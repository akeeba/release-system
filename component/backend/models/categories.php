<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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

class ArsModelCategories extends ArsModelBase
{
	function  buildQuery() {
		$where = array();

		$fltTitle		= $this->getState('title', null, 'string');
		$fltAlias		= $this->getState('alias', null, 'string');
		$fltDescription	= $this->getState('description', null, 'string');
		$fltType		= $this->getState('type', null, 'cmd');
		$fltAccess		= $this->getState('access', null, 'int');
		$fltPublished	= $this->getState('published', null, 'int');

		$db = $this->getDBO();
		if($fltTitle) {
			$where[] = '`title` LIKE "%'.$db->getEscaped($fltTitle).'%"';
		}
		if($fltAlias) {
			$where[] = '`alias` LIKE "%'.$db->getEscaped($fltAlias).'%"';
		}
		if($fltDescription) {
			$where[] = '`description` LIKE "%'.$db->getEscaped($fltDescription).'%"';
		}
		if($fltType) {
			$where[] = '`type` = '.$db->Quote($fltType);
		}
		if(!is_null($fltAccess)) {
			$where[] = '`access` = '.$db->Quote($fltAccess);
		}
		if(!is_null($fltPublished)) {
			$where[] = '`published` = '.$db->Quote($fltPublished);
		}

		$query = 'SELECT * FROM `#__ars_categories`';

		if(count($where))
		{
			$query .= ' WHERE (' . implode(') AND (',$where) . ')';
		}

		return $query;
	}
}