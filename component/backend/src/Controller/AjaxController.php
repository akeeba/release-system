<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\ItemsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class AjaxController extends BaseController
{
	/**
	 * Returns the HTML select element with the files for the selected release
	 *
	 * @throws Exception
	 */
	function getFiles(): void
	{
		// Token check
		$this->checkToken($this->input->getMethod());

		// Make sure this is a raw view
		if ($this->input->getCmd('format', 'html') != 'raw')
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Make sure the user has the create, edit or edit.own ACL privilege
		$user = Factory::getApplication()->getIdentity();

		if (
			!$user->authorise('core.create', 'com_ars') &&
			!$user->authorise('core.edit', 'com_ars') &&
			!$user->authorise('core.edit.own', 'com_ars')
		)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Get the information from the request
		$item_id    = $this->input->getInt('item_id', 0);
		$release_id = $this->input->getInt('release_id', 0);
		$selected   = $this->input->getString('selected', '');

		// Return the HTML list of files
		/** @var ItemsModel $model */
		$model   = $this->getModel('Items', 'Administrator');
		$options = $model->getFilesOptions($release_id, $item_id);

		@ob_end_clean();

		echo HTMLHelper::_('select.options', $options, 'value', 'text', $selected);

		Factory::getApplication()->close();
	}

}