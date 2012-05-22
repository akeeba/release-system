<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

if(!class_exists('ArsTable'))
{
	require_once JPATH_COMPONENT_ADMINISTRATOR.'/tables/base.php';
}

class TableCategories extends ArsTable
{
	var $id = 0;
	var $title = '';
	var $alias = '';
	var $description = '';
	var $type = '';
	var $groups = '';
	var $directory = '';
	var $vgroup_id = 0;
	var $created = null;
	var $created_by = 0;
	var $modified = '0000-00-00 00:00:00';
	var $modified_by = 0;
	var $checked_out = 0;
	var $checked_out_time = '0000-00-00 00:00:00';
	var $ordering = 0;
	var $access = 0;
	var $published = 0;
	var $language = '*';

	function __construct( &$db )
	{
		parent::__construct( '#__ars_categories', 'id', $db );
		
		$baseAccess = version_compare(JVERSION,'1.6.0','ge') ? 1 : 0;
		$this->access = $baseAccess;
	}

	function check()
	{
		// If the title is missing, throw an error
		if(!$this->title) {
			$this->setError(JText::_('ERR_CATEGORY_NEEDS_TITLE'));
			return false;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			jimport('joomla.filter.input');
			$alias = str_replace(' ', '-', strtolower($this->title));
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		// If no alias could be auto-generated, fail
		if(!$this->alias) {
			$this->setError(JText::_('ERR_CATEGORY_NEEDS_SLUG'));
			return false;
		}

		// Check alias for uniqueness
		$db = $this->getDBO();
		$sql = 'SELECT `alias` FROM `#__ars_categories`';
		if($this->id) $sql .= ' WHERE NOT(`id`='.(int)$this->id.')';
		$db->setQuery($sql);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$aliases = $db->loadColumn();
		} else {
			$aliases = $db->loadResultArray();
		}
		if(in_array($this->alias, $aliases))
		{
			$this->setError(JText::_('ERR_CATEGORY_NEEDS_UNIQUE_SLUG'));
			return false;
		}

		// Check directory
		jimport('joomla.filesystem.folder');
		
		$this->directory = rtrim($this->directory,'/');
		if($this->directory == 's3:') {
			$this->directory = 's3://';
		}
		$check = trim($this->directory);
		if(!empty($check)) {
			$potentialPrefix = substr($check, 0, 5);
			$potentialPrefix = strtolower($potentialPrefix);
			if($potentialPrefix == 's3://') {
				$check = substr($check, 5);
				if(!empty($check)) $check .= '/';
				$s3 = ArsHelperAmazons3::getInstance();
				$items = $s3->getBucket('', $check);
				if(empty($items)) {
					$this->setError(JText::_('ERR_CATEGORY_S3_DIRECTORY_NOT_EXISTS'));
					return false;
				}
			} else {
				$check = JPath::clean($check);
				if(!JFolder::exists($this->directory)) {
					$directory = JPATH_SITE.'/'.$this->directory;
					if(!JFolder::exists($directory)) {
						$this->setError(JText::_('ERR_CATEGORY_DIRECTORY_NOT_EXISTS'));
						return false;
					}
				}
			}
		} else {
			$this->setError(JText::_('ERR_CATEGORY_NEEDS_DIRECTORY'));
			return false;
		}

		// Automaticaly fix the type
		if(!in_array($this->type, array('normal','bleedingedge')))
		{
			$this->type = 'normal';
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);

		// Set the access to registered if there are subscriptions groups defined
		$baseAccess = version_compare(JVERSION,'1.6.0','ge') ? 1 : 0;
		if(!empty($this->groups) && ($this->access == $baseAccess))
		{
			$this->access = $baseAccess + 1;
		}

		jimport('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if(!$this->created_by && empty($this->id))
		{
			$this->created_by = $user->id;
			$this->created = $date->toMySQL();
		}
		else
		{
			$this->modified_by = $user->id;
			$this->modified = $date->toMySQL();
		}

		/*
		 if(empty($this->ordering)) {
			$this->ordering = $this->getNextOrder();
		}
		*/

		if(empty($this->published) && ($this->published !== 0) )
		{
			$this->published = 0;
		}

		return true;
	}

	function delete( $oid=null )
	{
		$joins = array(
			array(
				'label'		=> 'version',
				'name'		=> '#__ars_releases',
				'idfield'	=> 'id',
				'idalias'	=> 'rel_id',
				'joinfield'	=> 'category_id'
			)
		);
		if($this->canDelete($oid, $joins))
		{
			return parent::delete($oid);
		}
		else
		{
			return false;
		}
	}

}