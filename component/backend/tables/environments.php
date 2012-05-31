<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableEnvironments extends FOFTable
{
	/**
	 * Instantiate the table object
	 * 
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct( &$db )
	{
		parent::__construct( '#__ars_environments', 'id', $db );
	}
}