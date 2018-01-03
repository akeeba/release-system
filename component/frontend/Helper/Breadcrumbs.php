<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Helper;

defined('_JEXEC') or die;

use JFactory;
use JMenu;
use JRegistry;
use JRoute;

class Breadcrumbs
{
	public static function addRepositoryRoot($repoType = '')
	{
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getActive();

		$rootName = null;

		if (!is_object($menuitem) || !in_array(strtolower($menuitem->query['view']), ['browse', 'categories']))
		{
			$app = JFactory::getApplication();
			$pathway = $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);

			foreach ($all_items as $item)
			{
				$qOption = array_key_exists('option', $item->query) ? $item->query['option'] : '';
				$qView = array_key_exists('view', $item->query) ? $item->query['view'] : '';

				if ((!property_exists($item, 'published') || ($item->published))
					&& ($qOption == 'com_ars')
					&& ($qView == 'browse')
				)
				{
					if (array_key_exists('layout', $item->query) && ($item->query['layout'] == 'repository') && empty($Itemid))
					{
						$Itemid = $item->id;
						$rootName = $item->title;
						$rootURI = JRoute::_($item->link . '&Itemid=' . $Itemid);
					}
					elseif (array_key_exists('layout', $item->query) && $item->query['layout'] == $repoType)
					{
						$Itemid = $item->id;
						$rootName = $item->title;
						$rootURI = JRoute::_($item->link . '&Itemid=' . $Itemid);
					}
				}
			}

			if (!is_null($rootName) && isset($rootURI))
			{
				$pathway->addItem($rootName, $rootURI);
			}
		}
	}

	public static function addCategory($id, $name)
	{
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getActive();

		if (!is_object($menuitem) || !in_array(strtolower($menuitem->query['view']), ['category', 'releases']))
		{
			$app = JFactory::getApplication();
			$pathway = $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);
			if (empty($all_items))
			{
				return;
			}
			foreach ($all_items as $item)
			{
				if ((!property_exists($item, 'published') || ($item->published))
					&& ($item->query['option'] == 'com_ars')
					&& ($item->query['view'] == 'category')
				)
				{
					$params = is_object($item->params) ? $item->params : new JRegistry($item->params);
					if ($params->get('catid', 0) == $id)
					{
						$Itemid = $item->id;
						$rootName = $item->title;
						$rootURI = JRoute::_($item->link . '&Itemid=' . $Itemid);
					}
				}
			}

			if (is_null($Itemid))
			{
				$input = JFactory::getApplication()->input;
				$Itemid = $input->getInt('Itemid', null);
				$Itemid = empty($Itemid) ? '' : '&Itemid=' . $Itemid;

				$rootName = $name;
				$rootURI = JRoute::_('index.php?option=com_ars&view=Releases&category_id=' . $id . $Itemid);
			}

			if (!is_null($rootName) && isset($rootURI))
			{
				$pathway->addItem($rootName, $rootURI);
			}
		}
	}

	public static function addRelease($id, $name)
	{
		$menus = JMenu::getInstance('site');
		$menuitem = $menus->getActive();

		if (!is_object($menuitem) || !in_array(strtolower($menuitem->query['view']), ['release', 'items']))
		{
			$app = JFactory::getApplication();
			$pathway = $app->getPathway();

			// Preferably find a menu item linking to a specific repository type
			$Itemid = null;
			$all_items = $menus->getItems('type', 'component', false);
			foreach ($all_items as $item)
			{
				$qOption = array_key_exists('option', $item->query) ? $item->query['option'] : '';
				$qView = array_key_exists('view', $item->query) ? $item->query['view'] : '';
				$qLayout = array_key_exists('layout', $item->query) ? $item->query['layout'] : '';

				if ((!property_exists($item, 'published') || ($item->published))
					&& ($qOption == 'com_ars')
					&& ($qView == 'release')
				)
				{
					$params = is_object($item->params) ? $item->params : new JRegistry($item->params);
					if ($params->get('relid', 0) == $id)
					{
						$Itemid = $item->id;
						$rootName = $item->title;
						$rootURI = JRoute::_($item->link . '&Itemid=' . $Itemid);
					}
				}
			}

			if (is_null($Itemid))
			{
				$input = JFactory::getApplication()->input;
				$Itemid = $input->getInt('Itemid', null);
				$Itemid = empty($Itemid) ? '' : '&Itemid=' . $Itemid;

				$rootName = $name;
				$rootURI = JRoute::_('index.php?option=com_ars&view=Items&release_id=' . $id . $Itemid);
			}

			if (isset($rootName) && isset($rootURI))
			{
				$pathway->addItem($rootName, $rootURI);
			}
		}
	}
}
