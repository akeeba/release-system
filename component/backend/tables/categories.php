<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.database.table');

class TableCategories extends JTable
{
	var $id = 0;
	var $title = '';
	var $alias = '';
	var $description = '';
	var $type = '';
	var $groups = '';
	var $directory = '';
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
		parent::__construct( '#__ars_categories', 'id', $db );
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

		if(!$this->alias) {
			$this->setError(JText::_('ERR_CATEGORY_NEEDS_SLUG'));
			return false;
		}

		jimport('joomla.filesystem.folder');
		if(!JFolder::exists($this->directory)) {
			$directory = JPATH_SITE.DS.$this->directory;
			if(!JFolder::exists($directory)) {
				$this->setError(JText::_('ERR_CATEGORY_NEEDS_DIRECTORY'));
				return false;
			}
		}

		// Automaticaly fix the type
		if(!in_array($this->type, array('normal','bleedingedge')))
		{
			$this->type = 'normal';
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);

		// Set the access to registered if there are Ambra groups defined
		/*
		if(!empty($this->groups) && ($this->access == 0))
		{
			$this->access = 1;
		}
		*/

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