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

class TableVgroups extends ArsTable
{
	var $id = 0;
	var $title = '';
	var $created = null;
	var $created_by = 0;
	var $modified = '0000-00-00 00:00:00';
	var $modified_by = 0;
	var $checked_out = 0;
	var $checked_out_time = '0000-00-00 00:00:00';
	var $ordering = 0;
	var $published = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_vgroups', 'id', $db );		
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

	function delete( $oid=null )
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
		if($this->canDelete($oid, $joins)) {
			return parent::delete($oid);
		} else {
			return false;
		}
	}

}