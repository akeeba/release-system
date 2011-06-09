<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted access');

class ArsHelperBreadcrumbs
{
	public static function addRepositoryRoot($repoType = '')
	{
		$menus =& JMenu::getInstance('site');
		$menuitem =& $menus->getActive();
		
		$rootName = null;

		if (!is_object($menuitem) || $menuitem->query['view'] != 'browse')
		{
			$app = JFactory::getApplication();
			$pathway =& $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);
			foreach($all_items as $item)
			{
				if( (!property_exists($item, 'published') || ($item->published))
					&& ($item->query['option'] == 'com_ars')
					&& ($item->query['view'] == 'browse')
				)
				{
					if( array_key_exists('layout', $item->query) && ($item->query['layout'] == 'repository') && empty($Itemid) )
					{
						$Itemid = $item->id;
						$rootName = version_compare(JVERSION, '1.6.0', 'ge') ? $item->title : $item->name;
						$rootURI = JRoute::_($item->link.'&Itemid='.$Itemid);
					} elseif( array_key_exists('layout', $item->query) && $item->query['layout'] == $repoType ) {
						$Itemid = $item->id;
						$rootName = version_compare(JVERSION, '1.6.0', 'ge') ? $item->title : $item->name;
						$rootURI = JRoute::_($item->link.'&Itemid='.$Itemid);
					}
				}
			}
			
			if(!is_null($rootName))
				$pathway->addItem($rootName, $rootURI);
		}
	}

	public static function addCategory($id, $name)
	{
		$menus =& JMenu::getInstance('site');
		$menuitem =& $menus->getActive();

		if (!is_object($menuitem) ||$menuitem->query['view'] != 'category')
		{
			$app = JFactory::getApplication();
			$pathway = $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$isJoomla16 = version_compare(JVERSION,'1.6.0','ge');
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);
			if(empty($all_items)) return;
			foreach($all_items as $item)
			{
				if( (!property_exists($item, 'published') || ($item->published))
					&& ($item->query['option'] == 'com_ars')
					&& ($item->query['view'] == 'category')
				)
				{
					$params = is_object($item->params) ? $item->params : new JParameter($item->params);
					if( $params->get('catid',0) == $id ) {
						$Itemid = $item->id;
						$rootName = version_compare(JVERSION, '1.6.0', 'ge') ? $item->title : $item->name;
						$rootURI = JRoute::_($item->link.'&Itemid='.$Itemid);
					}
				}
			}

			if(is_null($Itemid))
			{
				$Itemid = JRequest::getInt('Itemid', null);
				$Itemid = empty($Itemid) ? '' : '&Itemid='.$Itemid;

				$rootName = $name;
				$rootURI = JRoute::_('index.php?option=com_ars&view=category&id='.$id.$Itemid);
			}

			$pathway->addItem($rootName, $rootURI);
		}
	}

	public static function addRelease($id, $name)
	{
		$menus =& JMenu::getInstance('site');
		$menuitem =& $menus->getActive();

		if (!is_object($menuitem) || $menuitem->query['view'] != 'release')
		{
			$app = JFactory::getApplication();
			$pathway = $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);
			foreach($all_items as $item)
			{
				if( (!property_exists($item, 'published') || ($item->published))
					&& ($item->query['option'] == 'com_ars')
					&& ($item->query['view'] == 'release')
				)
				{
					$params = is_object($item->params) ? $item->params : new JParameter($item->params);
					if( $params->get('relid',0) == $id ) {
						$Itemid = $item->id;
						$rootName = version_compare(JVERSION, '1.6.0', 'ge') ? $item->title : $item->name;
						$rootURI = JRoute::_($item->link.'&Itemid='.$Itemid);
					}
				}
			}

			if(is_null($Itemid))
			{
				$Itemid = JRequest::getInt('Itemid', null);
				$Itemid = empty($Itemid) ? '' : '&Itemid='.$Itemid;

				$rootName = $name;
				$rootURI = JRoute::_('index.php?option=com_ars&view=release&id='.$id.$Itemid);
			}

			$pathway->addItem($rootName, $rootURI);
		}
	}
	
}