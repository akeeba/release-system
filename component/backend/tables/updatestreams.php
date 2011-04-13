<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'base.php';

class TableUpdatestreams extends ArsTable
{
	var $id = 0;
	var $name = '';
	var $alias = '';
	var $type = 'components';
	var $category = 0;
	var $element = '';
	var $packname = '';
	var $created = '0000-00-00 00:00:00';
	var $created_by = 0;
	var $modified = '0000-00-00 00:00:00';
	var $modified_by = 0;
	var $checked_out_time = '0000-00-00 00:00:00';
	var $checked_out = 0;
	var $published = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_updatestreams', 'id', $db );
	}

	function check()
	{
		// If the title is missing, throw an error
		if(!$this->name) {
			$this->setError(JText::_('ERR_USTREAM_NEEDS_NAME'));
			return false;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			jimport('joomla.filter.input');
			$alias = str_replace(' ', '-', strtolower($this->name));
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		// If no alias could be auto-generated, fail
		if(!$this->alias) {
			$this->setError(JText::_('ERR_USTREAM_NEEDS_ALIAS'));
			return false;
		}

		// Check alias for uniqueness
		$db = $this->getDBO();
		$sql = 'SELECT `alias` FROM `#__ars_updatestreams`';
		if($this->id) $sql .= ' WHERE NOT(`id`='.(int)$this->id.')';
		$db->setQuery($sql);
		$aliases = $db->loadResultArray();
		if(in_array($this->alias, $aliases))
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_UNIQUE_ALIAS'));
			return false;
		}

		// Automaticaly fix the type
		if(!in_array($this->type, array('components','libraries','modules','packages','plugins','files','templates')))
		{
			$this->type = 'components';
		}

		// Require an element name
		if(empty($this->element))
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_ELEMENT'));
			return false;
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

		if(empty($this->published) && ($this->published !== 0) )
		{
			$this->published = 0;
		}

		return true;
	}
}