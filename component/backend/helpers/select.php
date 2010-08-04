<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted access');

class ArsHelperSelect
{
	protected static function genericlist($list, $name, $attribs, $selected, $idTag)
	{
		if(empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';
			foreach($attribs as $key=>$value)
			{
				$temp .= $key.' = "'.$value.'"';
			}
			$attribs = $temp;
		}

		return JHTML::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	public static function categorytypes($selected = null, $id = 'type', $attribs = array() )
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_CATEGORIES_TYPE_SELECT').' -');
		$options[] = JHTML::_('select.option','normal',JText::_('LBL_CATEGORIES_TYPE_NORMAL'));
		$options[] = JHTML::_('select.option','cleedingedge',JText::_('LBL_CATEGORIES_TYPE_BLEEDINGEDGE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function published($selected = null, $id = 'enabled', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_SELECT_STATE').' -');
		$options[] = JHTML::_('select.option',1,JText::_('UNPUBLISHED'));
		$options[] = JHTML::_('select.option',1,JText::_('PUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function ambragroups($selected = null, $name = 'groups')
	{
		if(!is_array($selected))
		{
			if(empty($selected)) {
				$selected = array();
			} else {
				$selected = explode(',',$selected);
			}
		}

		$db = JFactory::getDBO();
		$sql = 'SELECT * FROM `#__ambrasubs_types` WHERE `published` = 1';
		$db->setQuery($sql);
		$groups = $db->loadObjectList();

		$html = '';

		if(count($groups))
		{
			$options = array();
			foreach($groups as $group) {
				$item = '<input type="checkbox" class="checkbox" name="'.$name.'[]" value="'.$group->id.'" ';
				if(in_array($group->id, $selected)) $item .= ' checked="checked" ';
				$item .= '/> '.$group->title;
				$options[] = $item;
			}
			$html = implode("\n&nbsp;", $options);
		}

		return $html;
	}
}