<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

if(!class_exists('ArsTable'))
{
	require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'base.php';
}

class TableReleases extends ArsTable
{
	var $id = 0;
	var $category_id = 0;
	var $version = '';
	var $alias = '';
	var $maturity = 'alpha';
	var $description = '';
	var $notes= '';
	var $groups = '';
	var $hits = 0;
	var $created = null;
	var $created_by = 0;
	var $modified = '0000-00-00 00:00:00';
	var $modified_by = 0;
	var $checked_out = 0;
	var $checked_out_time = '0000-00-00 00:00:00';
	var $ordering = 0;
	var $access = 0;
	var $published = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_releases', 'id', $db );
		
		$baseAccess = version_compare(JVERSION,'1.6.0','ge') ? 1 : 0;
		$this->access = $baseAccess;
	}

	function check()
	{
		// If the category is missing, throw an error
		if(!$this->category_id) {
			$this->setError(JText::_('ERR_RELEASE_NEEDS_CATEGORY'));
			return false;
		}

		// Get some useful info
		$db = $this->getDBO();
		$sql = 'SELECT `version`, `alias` FROM `#__ars_releases` WHERE `category_id` = '.(int)$this->category_id;
		if($this->id) {
			$sql .= ' AND NOT(`id`='.(int)$this->id.')';
		}
		$db->setQuery($sql);
		$info = $db->loadAssocList();
		$versions = array(); $aliases = array();
		foreach($info as $infoitem)
		{
			$versions[] = $infoitem['version'];
			$aliases[] = $infoitem['alias'];
		}

		if(!$this->version) {
			$this->setError(JText::_('ERR_RELEASE_NEEDS_VERSION'));
			return false;
		}

		if(in_array($this->version, $versions)) {
			$this->setError(JText::_('ERR_RELEASE_NEEDS_VERSION_UNIQUE'));
			return false;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			jimport('joomla.filter.input');

			// Get the category title
			if(!class_exists('ArsModelCategories')) {
				require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'categories.php';
			}
			$catModel = new ArsModelCategories();
			$catModel->setId($this->category_id);
			$catItem = $catModel->getItem();

			// Create a smart alias
			$alias = strtolower($catItem->alias.'-'.$this->version);
			$alias = str_replace(' ', '-', $alias);
			$alias = str_replace('.', '-', $alias);
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		if(!$this->alias) {
			$this->setError(JText::_('ERR_RELEASE_NEEDS_ALIAS'));
			return false;
		}

		if(in_array($this->alias, $aliases)) {
			$this->setError(JText::_('ERR_RELEASE_NEEDS_ALIAS_UNIQUE'));
			return false;
		}

		// Automaticaly fix the maturity
		if(!in_array($this->maturity, array('alpha','beta','rc','stable')))
		{
			$this->maturity = 'beta';
		}

		jimport('joomla.filter.filterinput');
		$filter = JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if(!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Filter the notes using a safe HTML filter
		if(!empty($this->notes))
		{
			$this->notes = $filter->clean($this->notes);
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);
		// Set the access to registered if there are Ambra groups defined
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
			if(empty($this->created)) $this->created = $date->toMySQL();
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
}