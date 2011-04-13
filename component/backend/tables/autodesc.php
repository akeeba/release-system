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

class TableAutodesc extends ArsTable
{
	var $id = 0;
	var $category = 0;
	var $packname = '';
	var $title = '';
	var $description = '';
	var $published = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_autoitemdesc', 'id', $db );
	}

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

		return true;
	}
}