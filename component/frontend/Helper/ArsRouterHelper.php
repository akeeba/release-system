<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class ArsRouterHelper
{
	/**
	 * Get a value from a key-value array and eliminate the key from it
	 *
	 * If the key is not found in the array the default value is returned
	 *
	 * @param array  $query   They key-value array
	 * @param string $key     The key to retrieve and eliminate from the array
	 * @param mixed  $default The default value to use if the key does not exist in the array.
	 *
	 * @return mixed The retrieved value (or the default, if the key was not present)
	 */
	public static function getAndPop(array &$query, string $key, $default = null)
	{
		if (!isset($query[$key]))
		{
			return $default;
		}

		$value = $query[$key];

		unset($query[$key]);

		return $value;
	}

	/**
	 * Finds a menu whose query parameters match those in $qoptions
	 *
	 * @param array $qoptions The query parameters to look for
	 * @param array $params   The menu parameters to look for
	 *
	 * @return null|object Null if not found, or the menu item if we did find it
	 * @throws Exception
	 */
	public static function findMenu(array $qoptions = [], ?array $params = null): ?object
	{
		$input = Factory::getApplication()->input;

		// Convert $qoptions to an object
		if (empty($qoptions) || !is_array($qoptions))
		{
			$qoptions = [];
		}

		$menus    = AbstractMenu::getInstance('site');
		$menuitem = $menus->getActive();

		// First check the current menu item (fastest shortcut!)
		if (is_object($menuitem) && self::checkMenu($menuitem, $qoptions, $params))
		{
			return $menuitem;
		}

		// Find all potential menu items
		$possible_items = [];

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
		$app      = Factory::getApplication();
		$langCode = $input->getCmd('language', '*');

		if ($app->getLanguageFilter())
		{
			$lang_filter_plugin = PluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg       = Factory::getLanguage();
				$langCode = $lg->getTag();
			}
		}

		if ($langCode == '*')
		{
			// No language filtering required, return the first item
			return array_shift($possible_items);
		}

		// Filter for exact language or *
		foreach ($possible_items as $item)
		{
			if (in_array($item->language, [$langCode, '*']))
			{
				return $item;
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
	 * @throws Exception
	 */
	public static function checkMenu(object $menu, array $qoptions, ?array $params = null): bool
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

			if (!isset($query[$key]))
			{
				return false;
			}

			if ($query[$key] != $value)
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

		if (is_null($params))
		{
			return true;
		}

		$menus = AbstractMenu::getInstance('site');
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

				continue;
			}

			if ($check->get($key) != $value)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Precondition the SEF URL segments returned by Joomla
	 *
	 * Joomla replaces the first dash of each segment with a colon. For example /foo/bar-baz-bat will result in the
	 * array ['foo', 'bar:baz-bat']. This is based on the assumption that we write SEF URLs like Joomla used to back in
	 * 2005 when it was in version 1.0. Nowadays we want dashes to be kept as dashes. Hence this method which converts
	 * colons back to dashes.
	 *
	 * @param array $segments
	 *
	 * @return array
	 */
	public static function preconditionSegments(array $segments): array
	{
		$newSegments = [];

		if (empty($segments))
		{
			return [];
		}

		foreach ($segments as $segment)
		{
			if (strstr($segment, ':'))
			{
				$segment = str_replace(':', '-', $segment);
			}

			if (is_array($segment))
			{
				$newSegments[] = implode('-', $segment);

				continue;
			}

			$newSegments[] = $segment;
		}

		return $newSegments;
	}
}
