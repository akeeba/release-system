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

class ArsModelCategories extends ArsModelBase
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

		$fltTitle		= $this->getState('title', null, 'string');
		$fltAlias		= $this->getState('alias', null, 'string');
		$fltDescription	= $this->getState('description', null, 'string');
		$fltVgroup		= $this->getState('vgroup', null, 'int');
		$fltType		= $this->getState('type', null, 'cmd');
		$fltAccess		= $this->getState('access', null, 'cmd');
		$fltPublished	= $this->getState('published', null, 'int');
		$fltNoBEUnpub	= $this->getState('nobeunpub', null, 'int');
		$fltLanguage	= $this->getState('language', null, 'cmd');

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
		if($fltVgroup) {
			$where[] = '`vgroup_id` = '.$db->Quote($fltVgroup);
		}
		if($fltType) {
			$where[] = '`type` = '.$db->Quote($fltType);
		}
		if(!is_null($fltAccess)) {
			$where[] = '`access` = '.$db->Quote($fltAccess);
		}
		if($fltPublished != '') {
			$where[] = '`published` = '.$db->Quote((int)$fltPublished);
		}
		// No BleedingEdge unpublished releases
		if($fltNoBEUnpub) {
			$where[] = 'NOT (`published` = 0 AND `type` = "bleedingedge")';
		}
		
		if($fltLanguage) {
			$where[] = '`language` IN ("*", '.$db->quote($fltLanguage).')';
		}

		$query = 'SELECT * FROM `#__ars_categories`';

		if(count($where) && !$overrideLimits)
		{
			$query .= ' WHERE (' . implode(') AND (',$where) . ')';
		}
		
		if($fltNoBEUnpub && $overrideLimits) {
			$query .= ' WHERE NOT (`published` = 0 AND `type` = "bleedingedge")';
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