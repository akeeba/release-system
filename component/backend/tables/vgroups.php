<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableVgroups extends FOFTable
{
	function __construct( &$db )
	{
		parent::__construct( '#__ars_vgroups', 'id', $db );		
		
		$this->columnAlias = array(
			'enabled'		=> 'published',
			'slug'			=> 'alias',
			'created_on'	=> 'created',
			'modified_on'	=> 'modified',
			'locked_on'		=> 'checked_out_time',
			'locked_by'		=> 'checked_out',
		);
	}

	function check()
	{
		// If the title is missing, throw an error
		if(!$this->title) {
			$this->setError(JText::_('ERR_VGROUP_NEEDS_TITLE'));
			return false;
		}

		jimport('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if(!$this->created_by && empty($this->id)) {
			$this->created_by = $user->id;
			$this->created = $date->toMySQL();
		} else {
			$this->modified_by = $user->id;
			$this->modified = $date->toMySQL();
		}

		if(empty($this->ordering)) {
			$this->ordering = $this->getNextOrder();
		}

		if(empty($this->published) && ($this->published !== 0) ) {
			$this->published = 0;
		}

		return true;
	}

	
	/**
	 * Checks if we are allowed to delete this record
	 * 
	 * @param int $oid The numeric ID of the vgroup to delete
	 * 
	 * @return bool True if allowed to delete
	 */
	function onBeforeDelete( $oid=null )
	{
		$joins = array(
			array(
				'label'		=> 'categories',
				'name'		=> '#__ars_categories',
				'idfield'	=> 'id',
				'idalias'	=> 'vgroup_id',
				'joinfield'	=> 'vgroup_id'
			)
		);
		$result = $this->canDelete($oid, $joins);
		return $result && parent::onBeforeDelete($oid);
	}

}