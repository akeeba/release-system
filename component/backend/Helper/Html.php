<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF30\Container\Container;
use FOF30\Form\Field\GenericList;
use FOF30\Model\DataModel;
use FOF30\View\DataView\Raw;
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

	public static function ordering(Raw $view, string $orderingField, string $orderingValue): string
	{
		$ordering = $view->getLists()->order == $orderingField;
		$class    = 'input-mini';
		$icon     = 'icon-menu';

		// Default inactive ordering
		$html = '<span class="sortable-handler inactive" >';
		$html .= '<span class="' . $icon . '"></span>';
		$html .= '</span>';

		// The modern drag'n'drop method
		if ($view->getPerms()->editstate)
		{
			$disableClassName = '';
			$disabledLabel    = '';

			// DO NOT REMOVE! It will initialize Joomla libraries and javascript functions
			$hasAjaxOrderingSupport = $view->hasAjaxOrderingSupport();

			if (!$hasAjaxOrderingSupport['saveOrder'])
			{
				$disabledLabel    = Text::_('JORDERINGDISABLED');
				$disableClassName = 'inactive tip-top';
			}

			$orderClass = $ordering ? 'order-enabled' : 'order-disabled';

			$html = '<div class="' . $orderClass . '">';
			$html .= '<span class="sortable-handler ' . $disableClassName . '" title="' . $disabledLabel . '" rel="tooltip">';
			$html .= '<span class="' . $icon . '"></span>';
			$html .= '</span>';

			if ($ordering)
			{
				$joomla35IsBroken = version_compare(JVERSION, '3.5.0', 'ge') ? 'style="display: none"' : '';

				$html .= '<input type="text" name="order[]" ' . $joomla35IsBroken . ' size="5" class="' . $class . ' text-area-order" value="' . $orderingValue . '" />';
			}

			$html .= '</div>';
		}

		return $html;
	}

	public static function accessLevel(string $value, array $fieldOptions = []): string
	{
		/** @var array|null The select options coming from the access levels of the site */
		static $defaultOptions = null;

		$id    = isset($fieldOptions['id']) ? 'id="' . $fieldOptions['id'] . '" ' : '';
		$class = (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');

		if (is_null($defaultOptions))
		{
			$db    = Container::getInstance('com_ars')->platform->getDbo();
			$query = $db->getQuery(true)
				->select('a.id AS value, a.title AS text')
				->from('#__viewlevels AS a')
				->group('a.id, a.title, a.ordering')
				->order('a.ordering ASC')
				->order($db->qn('title') . ' ASC');

			// Get the options.
			$defaultOptions = $db->setQuery($query)->loadObjectList();
		}

		$options = $defaultOptions;

		array_unshift($options, HTMLHelper::_('select.option', '', Text::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')));

		return '<span ' . ($id ? $id : '') . ' class="' . $class . '">' .
			htmlspecialchars(GenericList::getOptionName($options, $value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

	public static function renderUserRepeatable(int $userid, array $attribs = []): string
	{
		static $userCache = [];

		// Initialise
		$show_username = isset($attribs['hide_username']) ? false : true;
		$show_email    = isset($attribs['hide_email']) ? false : true;
		$show_name     = isset($attribs['hide_name']) ? false : true;
		$show_id       = isset($attribs['hide_id']) ? false : true;
		$show_avatar   = isset($attribs['hide_avatar']) ? false : true;
		$show_link     = isset($attribs['show_link']) ? true : false;
		$link_url      = isset($attribs['link_url']) ? $attribs['link_url'] : null;
		$avatar_method = isset($attribs['avatar_method']) ? $attribs['avatar_method'] : 'gravatar';
		$avatar_size   = isset($attribs['avatar_size']) ? $attribs['avatar_size'] : 64;
		$class         = '';

		$key = is_numeric($userid) ? $userid : 'empty';
		$key = ($key == 0) ? 'zero' : $key;

		if (!array_key_exists($key, $userCache))
		{
			$userCache[$key] = static::getContainer()->platform->getUser($userid);
		}

		$user = $userCache[$key];

		if (!$link_url && static::getContainer()->platform->isBackend())
		{
			$link_url = 'index.php?option=com_users&task=user.edit&id=' . $userid;
		}
		elseif (!$link_url)
		{
			// If no link is defined in the front-end, we can't create a
			// default link. Therefore, show no link.
			$show_link = false;
		}

		// Get the avatar image, if necessary
		$avatar_url = '';

		if ($show_avatar)
		{
			if ($avatar_method == 'plugin')
			{
				// Use the user plugins to get an avatar
				static::getContainer()->platform->importPlugin('user');
				$jResponse = static::getContainer()->platform->runPlugins('onUserAvatar', [$user, $avatar_size]);

				if (!empty($jResponse))
				{
					foreach ($jResponse as $response)
					{
						if ($response)
						{
							$avatar_url = $response;
						}
					}
				}

				if (empty($avatar_url))
				{
					$show_avatar = false;
				}
			}
			else
			{
				// Fall back to the Gravatar method
				$md5    = md5($user->email);
				$scheme = Uri::getInstance()->getScheme();

				if ($scheme == 'http')
				{
					$avatar_url = 'http://www.gravatar.com/avatar/' . $md5 . '.jpg?s=' . $avatar_size . '&d=mm';
				}
				else
				{
					$avatar_url = 'https://secure.gravatar.com/avatar/' . $md5 . '.jpg?s=' . $avatar_size . '&d=mm';
				}
			}
		}

		// Generate the HTML
		$html = '<div ' . $class . '>';

		if ($show_avatar)
		{
			$html .= '<img src="' . $avatar_url . '" align="left" class="fof-usersfield-avatar" />';
		}

		if ($show_link)
		{
			$html .= '<a href="' . $link_url . '">';
		}

		if ($show_username)
		{
			$html .= '<span class="fof-usersfield-username">' . $user->username . '</span>';
		}

		if ($show_id)
		{
			$html .= '<span class="fof-usersfield-id">' . $user->id . '</span>';
		}

		if ($show_name)
		{
			$html .= '<span class="fof-usersfield-name">' . $user->name . '</span>';
		}

		if ($show_email)
		{
			$html .= '<span class="fof-usersfield-email">' . $user->email . '</span>';
		}

		if ($show_link)
		{
			$html .= '</a>';
		}

		$html .= '</div>';

		return $html;
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
}