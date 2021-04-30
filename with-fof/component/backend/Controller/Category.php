<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF40\Controller\DataController;
use Joomla\CMS\Language\Text;

class Category extends DataController
{
	protected function onBeforeAdd(): bool
	{
		if (!$this->checkACL('@Add'))
		{
			$returnUrl = 'index.php?option=' . $this->container->componentName . '&view=' . $this->container->inflector->pluralize($this->view) . $this->getItemidURLSuffix();

			$this->setRedirect(
				$returnUrl,
				Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'),
				'error'
			);

			return false;
		}

		$this->defaultsForAdd = [
			'type'         => 'normal',
			'access'       => 1,
			'published'    => 0,
			'is_supported' => 1,
			'language'     => '*',
		];

		foreach ($this->defaultsForAdd as $k => $v)
		{
			if ($stateValue = $this->getModel()->getState($k, $v))
			{
				$this->defaultsForAdd[$k] = $stateValue;
			}
		}

		return true;
	}
}
