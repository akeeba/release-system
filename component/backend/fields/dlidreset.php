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
class F0FFormFieldDlidreset extends F0FFormFieldText
{
	public function getRepeatable()
	{
		$this->element['show_link'] = 'true';

		$token = JFactory::getSession()->getFormToken();
		$this->element['url'] = JRoute::_('index.php?option=com_ars&view=dlidlabel&task=reset&id=' . $this->item->ars_dlidlabel_id . '&' . $token . '=1');

		if (!$this->element['class'])
		{
			$this->element['class'] = 'btn btn-micro btn-warning';
		}

		$this->element['format'] = '%s';

		$this->value = '<span class="icon-retweet"></span>';

		return parent::getRepeatable();
	}
}
