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
		$options[] = JHTML::_('select.option','bleedingedge',JText::_('LBL_CATEGORIES_TYPE_BLEEDINGEDGE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function published($selected = null, $id = 'enabled', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_SELECT_STATE').' -');
		$options[] = JHTML::_('select.option',0,JText::_('UNPUBLISHED'));
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

	public static function categories($selected = null, $id = 'category', $attribs = array())
	{
		if(!class_exists('ArsModelCategories')) {
			require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'categories.php';
		}
		$model = new ArsModelCategories(); // Do not use Singleton here!
		$model->reset();
		$items = $model->getItemList(true);

		$options = array();
		$options[] = JHTML::_('select.option',0,'- '.JText::_('LBL_CATEGORY_SELECT').' -');
		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->title);
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function maturities($selected = null, $id = 'maturity', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_RELEASES_MATURITY_SELECT').' -');
		
		$maturities = array('alpha','beta','rc','stable');
		foreach($maturities as $maturity) $options[] = JHTML::_('select.option',$maturity,JText::_('LBL_RELEASES_MATURITY_'.strtoupper($maturity)));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function releases($selected = null, $id = 'release', $attribs = array(), $category_id = null)
	{
		if(!class_exists('ArsModelReleases')) {
			require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'releases.php';
		}
		$model = new ArsModelReleases(); // Do not use Singleton here!
		$model->reset();
		if(!empty($category_id)) $model->setState('category', $category_id);
		$items = $model->getItemList(true);

		$options = array();

		if(count($items) && empty($category_id))
		{
			$cache = array();
			foreach($items as $item)
			{
				if(!array_key_exists($item->cat_title, $cache)) $cache[$item->cat_title] = array();
				$cache[$item->cat_title][] = (object)array('id' => $item->id, 'version' => $item->version);
			}

			foreach($cache as $category => $releases)
			{
				if(!empty($options)) $options[] = JHTML::_('select.option','</OPTGROUP>');
				$options[] = JHTML::_('select.option','<OPTGROUP>',$category);
				foreach($releases as $release)
				{
					$options[] = JHTML::_('select.option',$release->id,$release->version);
				}
			}
		}
		elseif(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->version);
		}

	   array_unshift($options, JHTML::_('select.option',0,'- '.JText::_('LBL_RELEASES_SELECT').' -'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function itemtypes($selected = null, $id = 'type', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_ITEMS_TYPE_SELECT').' -');

		$types = array('file','link');
		foreach($types as $type) $options[] = JHTML::_('select.option',$type,JText::_('LBL_ITEMS_TYPE_'.strtoupper($type)));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function getFiles($selected = null, $release_id = 0, $item_id = 0, $id = 'type', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_ITEMS_FILENAME_SELECT').' -');

		// Try to figure out a directory
		$directory = null;
		if(!empty($release_id))
		{
			// Get the release
			if(!class_exists('ArsModelReleases')) {
				require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'releases.php';
			}
			$relModel = new ArsModelReleases(); // Do not use Singleton here!
			$relModel->reset();
			$relModel->setId((int)$release_id);
			$release = $relModel->getItem();
			
			// Get the category
			if(!class_exists('ArsModelCategories')) {
				require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'categories.php';
			}
			$catModel = new ArsModelCategories(); // Do not use Singleton here!
			$catModel->reset();
			$catModel->setId((int)$release->category_id);
			$category = $catModel->getItem();

			// Get which directory to use
			jimport('joomla.filesystem.folder');
			$directory = $category->directory;
			if(!JFolder::exists($directory))
			{
				$directory = JPATH_ROOT.DS.$directory;
				if(!JFolder::exists($directory)) {
					$directory = null;
				}
			}
		}

		// Get a list of files already used in this category (so as not to show them again, he he!)
		$files = array();
		if(!empty($directory))
		{
			if(!class_exists('ArsModelItems')) {
				require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'items.php';
			}
			$model = new ArsModelItems();
			$model->reset();
			$model->setState('category',$release->category_id);
			$model->setState('limitstart', 0);
			$model->setState('limit', 0);
			$items = $model->getItemList();

			if(!empty($items)) foreach($items as $item) {
				if(($item->id != $item_id) && !empty($item->filename)) {
					$files[] = $item->filename;
				}
				$files = array_unique($files);
			}
		}

		// Produce a list of files and remove the items in the $files array
		$useFiles = array();
		if(!empty($directory))
		{
			$allFiles = JFolder::files($directory, '.', 2);
			if(!empty($allFiles)) foreach($allFiles as $aFile)
			{
				if(in_array($aFile, $files)) continue;
				$useFiles[] = $aFile;
			}
		}

		$options = array();
		if(!empty($useFiles)) foreach($useFiles as $file)
		{
			$options[] = JHTML::_('select.option', $file, $file);
		}
			
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function updatetypes($selected = null, $id = 'type', $attribs = array() )
	{
		$types = array('components','libraries','modules','packages','plugins','files','templates');
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_UPDATETYPES_SELECT').' -');
		foreach($types as $type)
		{
			$options[] = JHTML::_('select.option',$type,JText::_('LBL_UPDATETYPES_'.strtoupper($type)));
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function updatestreams($selected = null, $id = 'updatestream', $attribs = array())
	{
		$model = JModel::getInstance('Updatestreams','ArsModel');
		$model->reset();
		$items = $model->getItemList(true);

		$options = array();
		$options[] = JHTML::_('select.option',0,'- '.JText::_('LBL_ITEMS_UPDATESTREAM_SELECT').' -');
		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->name.' ('.$item->element.')');
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
}