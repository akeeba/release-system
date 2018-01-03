<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\DataController;
use FOF30\Controller\Exception\ItemNotFound;

class Release extends DataController
{
	protected function onBeforeApplySave(&$data)
	{
		if ($data['category_id'])
		{
			$permission = $data['id'] ? 'core.edit' : 'core.create';

			if (!$this->container->platform->getUser()->authorise($permission, $this->container->componentName . '.category.' . $data['category_id']))
			{
				$message = $data['id'] ? 'JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED' : 'JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED';

				throw new \RuntimeException(\JText::_($message), 403);
			}
		}

		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}
	}

	protected function onBeforeAdd()
	{
		$this->defaultsForAdd = [
			'category_id' => 0,
			'maturity'    => 'stable',
			'access'      => 1,
			'published'   => 0,
			'language'    => '*',
		];

		foreach ($this->defaultsForAdd as $k => $v)
		{
			if ($stateValue = $this->getModel()->getState($k, $v))
			{
				$this->defaultsForAdd[$k] = $stateValue;
			}
		}
	}

	protected function onBeforeDelete()
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $model */
		$model = $this->getModel()->savestate(false);

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.delete', $this->container->componentName . '.category.' . $model->category_id))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}
	}

	protected function onBeforeEdit()
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $model */
		$model = $this->getModel()->savestate(false);

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.edit', $this->container->componentName . '.category.' . $model->category_id))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}
	}
}
