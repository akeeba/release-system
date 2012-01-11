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
		
		$db = $this->getDBO();
		
		$query	= "SELECT * FROM #__ars_environments";
		
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