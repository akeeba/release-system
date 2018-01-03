<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

class ArsRouterHelper
{
	public static function getAndPop(&$query, $key, $default = null)
	{
		if (isset($query[ $key ]))
		{
			$value = $query[ $key ];
			unset($query[ $key ]);

			return $value;
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Finds a menu whose query parameters match those in $qoptions
	 *
	 * @param array $qoptions The query parameters to look for
	 * @param array $params   The menu parameters to look for
	 *
	 * @return null|object Null if not found, or the menu item if we did find it
	 */
	public static function findMenu($qoptions = array(), $params = null)
	{
		$input = JFactory::getApplication()->input;

		// Convert $qoptions to an object
		if (empty($qoptions) || !is_array($qoptions))
		{
			$qoptions = array();
		}

		$menus    = JMenu::getInstance('site');
		$menuitem = $menus->getActive();

		// First check the current menu item (fastest shortcut!)
		if (is_object($menuitem))
		{
			if (self::checkMenu($menuitem, $qoptions, $params))
			{
				return $menuitem;
			}
		}

		// Find all potential menu items
		$possible_items = array();
		foreach ($menus->getMenu() as $item)
		{
			if (self::checkMenu($item, $qoptions, $params))
			{
				$possible_items[] = $item;
			}
		}

		// If no potential item exists, return null
		if (empty($possible_items))
		{
			return null;
		}

		// Filter by language, if required
		/** @var JApplicationSite $app */
		$app = JFactory::getApplication();

		if ($app->getLanguageFilter())
		{
			$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);
			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg       = JFactory::getLanguage();
				$langCode = $lg->getTag();
			}
			else
			{
				$langCode = $input->getCmd('language', '*');
			}
		}
		else
		{
			$langCode = $input->getCmd('language', '*');
		}

		if ($langCode == '*')
		{
			// No language filtering required, return the first item
			return array_shift($possible_items);
		}
		else
		{
			// Filter for exact language or *
			foreach ($possible_items as $item)
			{
				if (in_array($item->language, array($langCode, '*')))
				{
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * Checks if a menu item conforms to the query options and parameters specified
	 *
	 * @param object $menu     A menu item
	 * @param array  $qoptions The query options to look for
	 * @param array  $params   The menu parameters to look for
	 *
	 * @return bool
	 */
	public static function checkMenu($menu, $qoptions, $params = null)
	{
		$query = $menu->query;
		foreach ($qoptions as $key => $value)
		{
			//if(is_null($value)) continue;
			if ($key == 'language')
			{
				continue;
			}
			if (empty($value))
			{
				continue;
			}
			if (!isset($query[ $key ]))
			{
				return false;
			}
			if ($query[ $key ] != $value)
			{
				return false;
			}
		}

		if (isset($qoptions['language']))
		{
			if (($menu->language != $qoptions['language']) && ($menu->language != '*'))
			{
				return false;
			}
		}

		if (!is_null($params))
		{
			$menus = JMenu::getInstance('site');
			$check = $menu->params instanceof JRegistry ? $menu->params : $menus->getParams($menu->id);

			foreach ($params as $key => $value)
			{
				if (is_null($value))
				{
					continue;
				}
				if ($key == 'language')
				{
					$v = $check->get($key);
					if (($v != $value) && ($v != '*') && !empty($v))
					{
						return false;
					}
				}
				else
				{
					if ($check->get($key) != $value)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	public static function preconditionSegments($segments)
	{
		$newSegments = array();
		if (!empty($segments))
		{
			foreach ($segments as $segment)
			{
				if (strstr($segment, ':'))
				{
					$segment = str_replace(':', '-', $segment);
				}
				if (is_array($segment))
				{
					$newSegments[] = implode('-', $segment);
				}
				else
				{
					$newSegments[] = $segment;
				}
			}
		}

		return $newSegments;
	}
}
