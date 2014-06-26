<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Form Field class for the F0F framework
 * Shows the due date field, either as a calendar input or as a formatted due date field
 *
 * @since       2.0
 */
class F0FFormFieldDlidlabel extends F0FFormFieldText
{
	public function getRepeatable()
	{
		// Initialise
		$class = $this->id;
		$format_string = '';
		$format_if_not_empty = false;
		$parse_value = false;
		$show_link = false;
		$link_url = '';
		$empty_replacement = '';

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string)$this->element['class'];
		}

		if ($this->element['format'])
		{
			$format_string = (string)$this->element['format'];
		}

		if ($this->element['show_link'] == 'true')
		{
			$show_link = true;
		}

		if ($this->element['format_if_not_empty'] == 'true')
		{
			$format_if_not_empty = true;
		}

		if ($this->element['parse_value'] == 'true')
		{
			$parse_value = true;
		}

		if ($this->element['url'])
		{
			$link_url = $this->element['url'];
		}
		else
		{
			$show_link = false;
		}

		if ($show_link && ($this->item instanceof F0FTable))
		{
			$link_url = $this->parseFieldTags($link_url);
		}
		else
		{
			$show_link = false;
		}

		if ($this->element['empty_replacement'])
		{
			$empty_replacement = (string)$this->element['empty_replacement'];
		}

		// Get the (optionally formatted) value
		$value = $this->value;

		if ($this->item->primary)
		{
			$value = JText::_('COM_ARS_DLIDLABELS_LBL_DEFAULT');
			$class = 'label label-success';
			$show_link = false;
		}
		else
		{
			$class = '';
		}

		if (!empty($empty_replacement) && empty($this->value))
		{
			$value = JText::_($empty_replacement);
		}

		if ($parse_value)
		{
			$value = $this->parseFieldTags($value);
		}

		if (!empty($format_string) && (!$format_if_not_empty || ($format_if_not_empty && !empty($this->value))))
		{
			$format_string = $this->parseFieldTags($format_string);
			$value = sprintf($format_string, $value);
		}
		else
		{
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}

		// Create the HTML
		$html = '<span class="' . $class . '">';

		if ($show_link)
		{
			$html .= '<a href="' . $link_url . '">';
		}

		$html .= $value;

		if ($show_link)
		{
			$html .= '</a>';
		}

		$html .= '</span>';

		return $html;
	}
}
