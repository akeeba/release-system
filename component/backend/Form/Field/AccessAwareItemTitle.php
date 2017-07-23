<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Form\Field;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Items;
use FOF30\Form\Field\Text;

class AccessAwareItemTitle extends Text
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
		$user = \JFactory::getUser();

		if ($user->authorise('core.admin'))
		{
			return parent::getRepeatable();
		}

		/** @var Items $item */
		$item = $this->item;

		$permission = 'com_ars.category.'.$item->release->category_id;

		if (!$user->authorise('core.edit', $permission))
		{
			$this->element['url'] = false;
		}

		return parent::getRepeatable();
	}
}
