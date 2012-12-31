<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableEnvironment extends FOFTable
{
	/**
	 * Instantiate the table object
	 * 
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct( $table, $key, &$db )
	{
		parent::__construct( '#__ars_environments', 'id', $db );
		
		$this->_columnAlias = array(
			'enabled'		=> 'published',
			'slug'			=> 'alias',
			'created_on'	=> 'created',
			'modified_on'	=> 'modified',
			'locked_on'		=> 'checked_out_time',
			'locked_by'		=> 'checked_out',
		);
	}
}