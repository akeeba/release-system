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
class F0FFormFieldDlid extends F0FFormFieldText
{
	public function getRepeatable()
	{
		$prefix = $this->item->user_id . ':';

		if ($this->item->primary)
		{
			$prefix = '';
			$this->element['class'] = 'label label-inverse';
		}
		else
		{
			$this->element['class'] = '';
		}

		$this->value = $prefix . $this->item->dlid;

		return parent::getRepeatable();
	}
}
