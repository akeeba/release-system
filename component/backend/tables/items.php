<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'base.php';

class TableItems extends ArsTable
{
	var $id = 0;
	var $release_id = 0;
	var $title = '';
	var $alias = '';
	var $description = '';
	var $type = '';
	var $filename = '';
	var $url = '';
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
		parent::__construct( '#__ars_items', 'id', $db );
	}

	function check()
	{
		// If the category is missing, throw an error
		if(!$this->release_id) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_CATEGORY'));
			return false;
		}

		// Get some useful info
		$db = $this->getDBO();
		$sql = 'SELECT `title`, `alias` FROM `#__ars_items` WHERE `release_id` = '.(int)$this->release_id;
		if($this->id) {
			$sql .= ' AND NOT(`id`='.(int)$this->id.')';
		}
		$db->setQuery($sql);
		$info = $db->loadRowList('version');
		$titles = array_keys($info);
		$aliases = array_values($info);

		if(!$this->title) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE'));
			return false;
		}

		if(in_array($this->title, $titles)) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE_UNIQUE'));
			return false;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			jimport('joomla.filter.input');

			// Create a smart alias
			$alias = strtolower($this->title);
			$alias = str_replace(' ', '-', $alias);
			$alias = str_replace('.', '-', $alias);
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		if(!$this->alias) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS'));
			return false;
		}

		if(in_array($this->alias, $aliases)) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS_UNIQUE'));
			return false;
		}

		// Do we have a type?
		if(!in_array($this->type,array('link','file')))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_TYPE'));
			return false;
		}

		// Check for filename or url
		if( ($this->type == 'link') && !($this->filename) ) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_FILENAME'));
			return false;
		} elseif( ($this->type == 'link') && !($this->url) ) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_LINK'));
			return false;
		}

		jimport('joomla.filter.filterinput');
		$filter = JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if(!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);
		// Set the access to registered if there are Ambra groups defined
		if(!empty($this->groups) && ($this->access == 0))
		{
			$this->access = 1;
		}

		jimport('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if(!$this->created_by)
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
}