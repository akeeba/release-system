<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

use Akeeba\ReleaseSystem\Admin\Helper\Select;
use FOF30\Controller\Controller;

defined('_JEXEC') or die;

class Ajax extends Controller
{
	function getFiles()
	{
		// Token check
		$this->csrfProtection();

		// Make sure this is a raw view
		if ($this->input->getCmd('format', 'html') != 'raw')
		{
			$this->container->platform->raiseError(403, \JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));
		}

		// Make sure the user has the create, edit or edit.own ACL privilege
		$user = $this->container->platform->getUser();

		if (
			!$user->authorise('core.create', 'com_ars') &&
			!$user->authorise('core.edit', 'com_ars') &&
			!$user->authorise('core.edit.own', 'com_ars')
		)
		{
			$this->container->platform->raiseError(403, \JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));
		}

		// Get the information from the request
		$item_id    = $this->input->getInt('item_id', 0);
		$release_id = $this->input->getInt('release_id', 0);
		$selected   = $this->input->getString('selected', '');

		// Return the HTML list of files
		$result = Select::getFiles($selected, $release_id, $item_id, 'filename', array('onchange' => 'arsItems.onFileChange();'));

		@ob_end_clean();
		echo $result;

		$this->container->platform->closeApplication();
	}
}
