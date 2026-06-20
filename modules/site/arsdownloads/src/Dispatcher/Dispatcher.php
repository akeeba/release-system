<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Module\Arsdownload\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Dispatcher class for mod_arsdownloads
 *
 * @since  7.4.4
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
	use HelperFactoryAwareTrait;

	/**
	 * Returns the layout data.
	 *
	 * Returning boolean false stops the module from rendering anything, which is what we want when there are no
	 * items to display.
	 *
	 * @return  array|false
	 *
	 * @since   7.4.4
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		$data['items'] = $this->getHelperFactory()
			->getHelper('ArsdownloadHelper')
			->getItems($data['params'], $this->getApplication());

		if (empty($data['items']))
		{
			return false;
		}

		return $data;
	}
}
