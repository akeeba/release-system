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
class F0FFormFieldDlidpublished extends F0FFormFieldPublished
{
	public function getRepeatable()
	{
		$html = parent::getRepeatable();

		$html = str_replace('icon-publish', 'icon-ok', $html);
		$html = str_replace('icon-unpublish', 'icon-remove', $html);

		if ($this->item->primary)
		{
			$html = '<span class="btn btn-micro disabled"><span class="icon-ok"></span></span>';
		}

		return $html;
	}
}
