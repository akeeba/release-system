<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\Model\ItemsModel as AdminItemsModel;
use Joomla\CMS\Form\Form;

class ItemsModel extends AdminItemsModel
{
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = null)
	{
		$backendPath = JPATH_ADMINISTRATOR . '/components/com_ars';

		Form::addFormPath($backendPath . '/forms');
		Form::addFormPath($backendPath . '/models/forms');
		Form::addFieldPath($backendPath . '/models/fields');
		Form::addFormPath($backendPath . '/model/form');
		Form::addFieldPath($backendPath . '/model/field');

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

}