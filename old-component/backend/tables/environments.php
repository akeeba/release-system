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

class TableEnvironments extends ArsTable
{
	var $id = 0;
	var $title = '';
	var $xmltitle = '1.0';
	var $icon = '';
	
	function __construct( &$db )
	{
		parent::__construct( '#__ars_environments', 'id', $db );
	}
}