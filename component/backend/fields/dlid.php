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
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
					->select(array(
						'md5(concat(' . $db->qn('id') . ',' . $db->qn('username') . ',' . $db->qn('password') . ')) AS ' . $db->qn('dlid')
					))
					->from($db->qn('#__users'))
					->where($db->qn('id') . ' = ' . $db->q($this->item->user_id));
		$db->setQuery($query);
		$masterDlid = $db->loadResult();

		$this->value = $this->item->user_id . ':' . md5($this->item->user_id . $this->item->label . $masterDlid);

		return parent::getRepeatable();
	}
}
