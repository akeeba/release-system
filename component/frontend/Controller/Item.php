<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Controller;


use FOF30\Controller\DataController;

class Item extends DataController
{
	public function execute($task)
	{
		$format = $this->input->getCmd('format', 'html');

		// Only JSON views are allowed
		if ($format != 'json')
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// JSON views require core.manage privileges
		if (!$this->checkACL('core.manage'))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		return parent::execute($task);
	}

}