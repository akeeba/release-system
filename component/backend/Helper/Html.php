<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF30\Container\Container;
use FOF30\Form\Field\GenericList;
use FOF30\Model\DataModel;
use FOF30\View\DataView\DataViewInterface;
use FOF30\View\DataView\Raw;
use JHelperUsergroups;
use JHtml;
use JText;

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
	private static function getContainer()
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_ars');
		}

		return self::$container;
	}

	public static function language($value)
	{
		static $languages;

		if (!$languages)
		{
			$db = \JFactory::getDbo();

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

		$lang = \JText::_('JALL');

		if (isset($languages[$value]))
		{
			$lang = $languages[$value]->title;
		}

		return '<span>'.$lang.'</span>';
	}

	public static function decodeUpdateType($value)
	{
		switch ($value)
		{
			case 'components':
				return JText::_('LBL_UPDATETYPES_COMPONENTS');
			case 'libraries':
				return JText::_('LBL_UPDATETYPES_LIBRARIES');
			case 'modules':
				return JText::_('LBL_UPDATETYPES_MODULES');
			case 'packages':
				return JText::_('LBL_UPDATETYPES_PACKAGES');
			case 'plugins':
				return JText::_('LBL_UPDATETYPES_PLUGINS');
			case 'files':
				return JText::_('LBL_UPDATETYPES_FILES');
			case 'templates':
				return JText::_('LBL_UPDATETYPES_TEMPLATES');
			default:
				return '';
		}
	}

	public static function ordering(Raw $view, $orderingField, $orderingValue)
	{
		$ordering = $view->getLists()->order == $orderingField;
		$class = 'input-mini';
		$icon = 'icon-menu';

		// Default inactive ordering
		$html  = '<span class="sortable-handler inactive" >';
		$html .= '<span class="' . $icon . '"></span>';
		$html .= '</span>';

		// The modern drag'n'drop method
		if ($view->getPerms()->editstate)
		{
			$disableClassName = '';
			$disabledLabel = '';

			// DO NOT REMOVE! It will initialize Joomla libraries and javascript functions
			$hasAjaxOrderingSupport = $view->hasAjaxOrderingSupport();

			if (!$hasAjaxOrderingSupport['saveOrder'])
			{
				$disabledLabel = JText::_('JORDERINGDISABLED');
				$disableClassName = 'inactive tip-top';
			}

			$orderClass = $ordering ? 'order-enabled' : 'order-disabled';

			$html  = '<div class="' . $orderClass . '">';
			$html .= 	'<span class="sortable-handler ' . $disableClassName . '" title="' . $disabledLabel . '" rel="tooltip">';
			$html .= 		'<span class="' . $icon . '"></span>';
			$html .= 	'</span>';

			if ($ordering)
			{
				$joomla35IsBroken = version_compare(JVERSION, '3.5.0', 'ge') ? 'style="display: none"': '';

				$html .= '<input type="text" name="order[]" ' . $joomla35IsBroken . ' size="5" class="' . $class . ' text-area-order" value="' . $orderingValue . '" />';
			}

			$html .= '</div>';
		}

		return $html;
	}

	public static function accessLevel($value, array $fieldOptions = array())
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

		array_unshift($options, JHtml::_('select.option', '', JText::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')));

		return '<span ' . ($id ? $id : '') . ' class="'. $class . '">' .
			htmlspecialchars(GenericList::getOptionName($options, $value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

	public static function renderUserRepeatable($userid, array $attribs = array())
	{
		static $userCache = array();

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
			$link_url = 'index.php?option=com_users&task=user.edit&id='.$userid;
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
				$jResponse = static::getContainer()->platform->runPlugins('onUserAvatar', array($user, $avatar_size));

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
				$md5 = md5($user->email);
				$scheme = \JUri::getInstance()->getScheme();

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
			$html .= '<span class="fof-usersfield-username">' . $user->username	. '</span>';
		}

		if ($show_id)
		{
			$html .= '<span class="fof-usersfield-id">' . $user->id	. '</span>';
		}

		if ($show_name)
		{
			$html .= '<span class="fof-usersfield-name">' . $user->name	. '</span>';
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

	public static function rules($value, $assetField, $modelName, $component, $section = 'component')
	{
		/**
		 * At the timing of this writing (2013-12-03), the Joomla "rules" field is buggy. When you are
		 * dealing with a new record it gets the default permissions from the root asset node, which
		 * is fine for the default permissions of Joomla articles, but unsuitable for third party software.
		 * We had to copy & paste the whole code, since we can't "inject" the correct asset id if one is
		 * not found. Our fixes are surrounded by `FOF Library fix` remarks.
		 *
		 * @return  string  The input field's HTML for this field type
		 */
		JHtml::_('bootstrap.tooltip');

		// Get the actions for the asset.
		$actions = \JAccess::getActions($component, $section);

		// Get the explicit rules for this asset.
		if ($section == 'component')
		{
			// Need to find the asset id by the name of the component.
			$db    = self::getContainer()->platform->getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('name') . ' = ' . $db->quote($component));

			$assetId = (int) $db->setQuery($query)->loadResult();
		}
		else
		{
			// Find the asset id of the content.
			// Note that for global configuration, com_config injects asset_id = 1 into the form.
			$assetId = $value;

			// ==== FOF Library fix - Start ====
			// If there is no assetId (let's say we are dealing with a new record), let's ask the table
			// to give it to us. Here you should implement your logic (ie getting default permissions from
			// the component or from the category)
			if(!$assetId)
			{
				/** @var DataModel $table */
				$table   = self::getContainer()->factory->model($modelName)->tmpInstance();
				$assetId = $table->getAssetParentId();
			}
			// ==== FOF Library fix - End   ====
		}

		// Full width format.

		// Get the rules for just this asset (non-recursive).
		$assetRules = \JAccess::getAssetRules($assetId, true);

		// Get the available user groups.
		$groups = self::getUserGroups();

		// Prepare output
		$html = array();

		// Description
		$html[] = '<p class="rule-desc">' . JText::_('JLIB_RULES_SETTINGS_DESC') . '</p>';

		// Begin tabs
		$html[] = '<div id="permissions-sliders" class="tabbable tabs-left">';

		// Building tab nav
		$html[] = '<ul class="nav nav-tabs">';

		foreach ($groups as $group)
		{
			// Initial Active Tab
			$active = "";

			if ($group->value == 1)
			{
				$active = "active";
			}

			$html[] = '<li class="' . $active . '">';
			$html[] = '<a href="#permission-' . $group->value . '" data-toggle="tab">';
			$html[] = str_repeat('<span class="level">&ndash;</span> ', $curLevel = $group->level) . $group->text;
			$html[] = '</a>';
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		$html[] = '<div class="tab-content">';

		// Start a row for each user group.
		foreach ($groups as $group)
		{
			// Initial Active Pane
			$active = "";

			if ($group->value == 1)
			{
				$active = " active";
			}

			$html[] = '<div class="tab-pane' . $active . '" id="permission-' . $group->value . '">';
			$html[] = '<table class="table table-striped">';
			$html[] = '<thead>';
			$html[] = '<tr>';

			$html[] = '<th class="actions" id="actions-th' . $group->value . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_ACTION') . '</span>';
			$html[] = '</th>';

			$html[] = '<th class="settings" id="settings-th' . $group->value . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_SELECT_SETTING') . '</span>';
			$html[] = '</th>';

			// The calculated setting is not shown for the root group of global configuration.
			$canCalculateSettings = ($group->parent_id || !empty($component));

			if ($canCalculateSettings)
			{
				$html[] = '<th id="aclactionth' . $group->value . '">';
				$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_CALCULATED_SETTING') . '</span>';
				$html[] = '</th>';
			}

			$html[] = '</tr>';
			$html[] = '</thead>';
			$html[] = '<tbody>';

			foreach ($actions as $action)
			{
				$html[] = '<tr>';
				$html[] = '<td headers="actions-th' . $group->value . '">';
				$html[] = '<label for="' . $assetField . '_' . $action->name . '_' . $group->value . '" class="hasTooltip" title="'
					. htmlspecialchars(JText::_($action->title) . ' ' . JText::_($action->description), ENT_COMPAT, 'UTF-8') . '">';
				$html[] = JText::_($action->title);
				$html[] = '</label>';
				$html[] = '</td>';

				$html[] = '<td headers="settings-th' . $group->value . '">';

				$html[] = '<select class="input-small" name="' . $assetField . '[' . $action->name . '][' . $group->value . ']" id="' . $assetField . '_' . $action->name
					. '_' . $group->value . '" title="'
					. JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($action->title), trim($group->text)) . '">';

				$inheritedRule = \JAccess::checkGroup($group->value, $action->name, $assetId);

				// Get the actual setting for the action for this group.
				$assetRule = $assetRules->allow($action->name, $group->value);

				// Build the dropdowns for the permissions sliders

				// The parent group has "Not Set", all children can rightly "Inherit" from that.
				$html[] = '<option value=""' . ($assetRule === null ? ' selected="selected"' : '') . '>'
					. JText::_(empty($group->parent_id) && empty($component) ? 'JLIB_RULES_NOT_SET' : 'JLIB_RULES_INHERITED') . '</option>';
				$html[] = '<option value="1"' . ($assetRule === true ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_ALLOWED')
					. '</option>';
				$html[] = '<option value="0"' . ($assetRule === false ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_DENIED')
					. '</option>';

				$html[] = '</select>&#160; ';

				// If this asset's rule is allowed, but the inherited rule is deny, we have a conflict.
				if (($assetRule === true) && ($inheritedRule === false))
				{
					$html[] = JText::_('JLIB_RULES_CONFLICT');
				}

				$html[] = '</td>';

				// Build the Calculated Settings column.
				// The inherited settings column is not displayed for the root group in global configuration.
				if ($canCalculateSettings)
				{
					$html[] = '<td headers="aclactionth' . $group->value . '">';

					// This is where we show the current effective settings considering currrent group, path and cascade.
					// Check whether this is a component or global. Change the text slightly.

					if (\JAccess::checkGroup($group->value, 'core.admin', $assetId) !== true)
					{
						if ($inheritedRule === null)
						{
							$html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === true)
						{
							$html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === false)
						{
							if ($assetRule === false)
							{
								$html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
							}
							else
							{
								$html[] = '<span class="label"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_NOT_ALLOWED_LOCKED')
									. '</span>';
							}
						}
					}
					elseif (!empty($component))
					{
						$html[] = '<span class="label label-success"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
							. '</span>';
					}
					else
					{
						// Special handling for  groups that have global admin because they can't  be denied.
						// The admin rights can be changed.
						if ($action->name === 'core.admin')
						{
							$html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
						}
						elseif ($inheritedRule === false)
						{
							// Other actions cannot be changed.
							$html[] = '<span class="label label-important"><i class="icon-lock icon-white"></i> '
								. JText::_('JLIB_RULES_NOT_ALLOWED_ADMIN_CONFLICT') . '</span>';
						}
						else
						{
							$html[] = '<span class="label label-success"><i class="icon-lock icon-white"></i> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
								. '</span>';
						}
					}

					$html[] = '</td>';
				}

				$html[] = '</tr>';
			}

			$html[] = '</tbody>';
			$html[] = '</table></div>';
		}

		$html[] = '</div></div>';

		$html[] = '<div class="alert">';

		if ($section == 'component' || $section == null)
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES');
		}
		else
		{
			$html[] = JText::_('JLIB_RULES_SETTING_NOTES_ITEM');
		}

		$html[] = '</div>';

		return implode("\n", $html);
	}

	protected static function getUserGroups()
	{
		$options = JHelperUsergroups::getInstance()->getAll();

		foreach ($options as &$option)
		{
			$option->value = $option->id;
			$option->text  = $option->title;
		}

		return array_values($options);
	}
}