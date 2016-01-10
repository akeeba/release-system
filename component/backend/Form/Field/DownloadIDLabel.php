<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Form\Field;

defined('_JEXEC') or die;

use FOF30\Form\Field\Text;

class DownloadIDLabel extends Text
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		static $oldUrl = null;

		$this->class = ' ';

		if (!empty($oldUrl))
		{
			$this->element['url'] = $oldUrl;
		}

		if (($this->value == '_MAIN_') || $this->item->primary)
		{
			$this->class = 'label label-success';
			$this->value = \JText::_('COM_ARS_DLIDLABELS_LBL_DEFAULT');

			$oldUrl = (string) $this->element['url'];
			$this->element['url'] = '';
		}

		return parent::getRepeatable();
	}
}