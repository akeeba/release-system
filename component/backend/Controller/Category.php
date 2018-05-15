<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\View\Categories\Html;
use FOF30\Controller\DataController;

class Category extends DataController
{
	protected function onBeforeApplySave(&$data)
	{
		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}
	}

	protected function onBeforeAdd()
	{
		if (!$this->checkACL('@Add'))
		{
			$returnUrl = 'index.php?option=' . $this->container->componentName . '&view=' . $this->container->inflector->pluralize($this->view) . $this->getItemidURLSuffix();

			$this->setRedirect(
				$returnUrl,
				\JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'),
				'error'
			);

			return false;
		}

		$this->defaultsForAdd = [
			'vgroup_id'    => 0,
			'type'         => 'normal',
			'access'       => 1,
			'published'    => 0,
			'is_supported' => 1,
			'language'     => '*',
		];

		/** @var Html $view */
		$dataModel = $this->getModel();

		foreach ($this->defaultsForAdd as $k => $v)
		{
			$stateValue = $dataModel->getState($k);

			if (!is_null($stateValue))
			{
				$this->defaultsForAdd[$k] = $stateValue;
			}
		}
	}
}
