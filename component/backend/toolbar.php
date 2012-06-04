<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsToolbar extends FOFToolbar
{
	public function onImpjeds()
	{
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
		$this->renderSubmenu();
	}
	
	public function onUploads()
	{
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
		$this->renderSubmenu();
	}
}