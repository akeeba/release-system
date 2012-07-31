<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableAutodesc extends FOFTable
{
	/**
	 * Instantiate the table object
	 * 
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct( $table, $key, &$db )
	{
		parent::__construct( '#__ars_autoitemdesc', 'id', $db );
		
		$this->_columnAlias = array(
			'enabled'		=> 'published',
			'slug'			=> 'alias',
			'created_on'	=> 'created',
			'modified_on'	=> 'modified',
			'locked_on'		=> 'checked_out_time',
			'locked_by'		=> 'checked_out',
		);
	}

	/**
	 * Checks the record for validity
	 * 
	 * @return int True if the record is valid
	 */
	function check()
	{
		if(!$this->category) {
			$this->setError(JText::_('ERR_AUTODESC_NEEDS_CATEGORY'));
			return false;
		}

		if(!$this->packname) {
			$this->setError(JText::_('ERR_AUTODESC_NEEDS_PACKNAME'));
			return false;
		}

		if(!$this->title) {
			$this->setError(JText::_('ERR_AUTODESC_NEEDS_TITLE'));
			return false;
		}

		if(!$this->description) {
			$this->setError(JText::_('ERR_AUTODESC_NEEDS_DESCRIPTION'));
			return false;
		}

		if(empty($this->published) && ($this->published !== 0) )
		{
			$this->published = 0;
		}
		
		// Added environment ID
		$this->environments = json_encode( $this->environments );		

		return true;
	}
	
	/**
	 * Fires after loading a record, automatically unserialises the environments
	 * field (by default it's JSON-encoded)
	 * 
	 * @param object $result The loaded row
	 * 
	 * @return bool
	 */
	protected function onAfterLoad(&$result) {
		if ( is_string( $this->environments ) ) $this->environments = json_decode( $this->environments );
		parent::onAfterLoad($result);
		return $result;
	}
}