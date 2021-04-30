<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF40\Container\Container;
use FOF40\Form\Field\GenericList;
use FOF40\Model\DataModel;
use FOF40\View\DataView\Raw;
use JHelperUsergroups;
use JHtml;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\UserGroupsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

abstract class Html
{
	/**
	 * The component container
	 *
	 * @var   Container
	 */
	private static $container;

	/**
	 * Get the component's container
	 *
	 * @return  Container
	 */
	private static function getContainer(): Container
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_ars');
		}

		return self::$container;
	}

	public static function language(string $value): string
	{
		static $languages;

		if (!$languages)
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__languages'));

			$languages = $db->setQuery($query)->loadObjectList('lang_code');
		}

		// Unknown value
		if ($value != '*' && !isset($languages[$value]))
		{
			return '';
		}

		$lang = Text::_('JALL');

		if (isset($languages[$value]))
		{
			$lang = $languages[$value]->title;
		}

		return '<span>' . $lang . '</span>';
	}

	public static function decodeUpdateType(string $value): string
	{
		switch ($value)
		{
			case 'components':
				return Text::_('LBL_UPDATETYPES_COMPONENTS');
			case 'libraries':
				return Text::_('LBL_UPDATETYPES_LIBRARIES');
			case 'modules':
				return Text::_('LBL_UPDATETYPES_MODULES');
			case 'packages':
				return Text::_('LBL_UPDATETYPES_PACKAGES');
			case 'plugins':
				return Text::_('LBL_UPDATETYPES_PLUGINS');
			case 'files':
				return Text::_('LBL_UPDATETYPES_FILES');
			case 'templates':
				return Text::_('LBL_UPDATETYPES_TEMPLATES');
			default:
				return '';
		}
	}

	public static function accessLevel(string $value, array $fieldOptions = []): string
	{
		/** @var array|null The select options coming from the access levels of the site */
		static $defaultOptions = null;

		$id    = isset($fieldOptions['id']) ? 'id="' . $fieldOptions['id'] . '" ' : '';
		$class = (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');

		if (is_null($defaultOptions))
		{
			$defaultOptions = Select::accessLevel(false);
		}

		$options = array_merge([
			HTMLHelper::_('select.option', '', Text::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')),
		], $defaultOptions);

		return '<span ' . ($id ? $id : '') . ' class="' . $class . '">' .
			htmlspecialchars(self::getOptionName($options, $value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

	protected static function getUserGroups(): array
	{
		$options = UserGroupsHelper::getInstance()->getAll();

		foreach ($options as &$option)
		{
			$option->value = $option->id;
			$option->text  = $option->title;
		}

		return array_values($options);
	}

	/**
	 * Gets the active option's label given an array of JHtml options
	 *
	 * @param array  $data        The JHtml options to parse
	 * @param mixed  $selected    The currently selected value
	 * @param string $optKey      Key name
	 * @param string $optText     Value name
	 * @param bool   $selectFirst Should I automatically select the first option?
	 *
	 * @return  mixed   The label of the currently selected option
	 */
	public static function getOptionName($data, $selected = null, $optKey = 'value', $optText = 'text', $selectFirst = true)
	{
		$ret = null;

		foreach ($data as $elementKey => &$element)
		{
			if (is_array($element))
			{
				$key  = $optKey === null ? $elementKey : $element[$optKey];
				$text = $element[$optText];
			}
			elseif (is_object($element))
			{
				$key  = $optKey === null ? $elementKey : $element->$optKey;
				$text = $element->$optText;
			}
			else
			{
				// This is a simple associative array
				$key  = $elementKey;
				$text = $element;
			}

			if (is_null($ret) && $selectFirst)
			{
				$ret = $text;
			}
			elseif ($selected == $key)
			{
				$ret = $text;
			}
		}

		return $ret;
	}
}