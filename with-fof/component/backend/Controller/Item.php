<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF40\Container\Container;
use FOF40\Controller\DataController;
use FOF40\Controller\Exception\ItemNotFound;
use Joomla\CMS\Language\Text;

class Item extends DataController
{
	protected function onBeforeBrowse()
	{
		if ($this->getModel()->getState('limit', 20) != 0)
		{
			$this->getModel()->with([]);
		}
	}

	protected function onBeforeApplySave(array &$data): bool
	{
		if ($data['release_id'])
		{
			/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $releasesModel */
			$releasesModel = $this->getModel('Releases');
			$releasesModel->load($data['release_id']);

			$permission = $data['id'] ? 'core.edit' : 'core.create';

			if (!$this->container->platform->getUser()->authorise($permission, $this->container->componentName . '.category.' . $releasesModel->category_id))
			{
				$message = $data['id'] ? 'JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED' : 'JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED';

				throw new \RuntimeException(Text::_($message), 403);
			}
		}

		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['environments']))
		{
			$data['environments'] = [];
		}

		return true;
	}

	protected function onBeforeAdd(): bool
	{
		$this->defaultsForAdd = [
			'release_id' => 0,
			'type'       => 'file',
			'access'     => 1,
			'published'  => 0,
			'language'   => '*',
		];

		if ($stateValue = $this->getModel()->getState('release', null))
		{
			$this->defaultsForAdd['release_id'] = $stateValue;
		}

		foreach ($this->defaultsForAdd as $k => $v)
		{
			if ($stateValue = $this->getModel()->getState($k, $v))
			{
				$this->defaultsForAdd[$k] = $stateValue;
			}
		}

		return true;
	}

	protected function onBeforeDelete(): bool
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $model */
		$model = $this->getModel()->savestate(false);

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(Text::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.delete', $this->container->componentName . '.category.' . $model->release->category_id))
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}

		return true;
	}

	protected function onBeforeEdit(): bool
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $model */
		$model = $this->getModel()->savestate(false);

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(Text::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.edit', $this->container->componentName . '.category.' . $model->release->category_id))
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		return true;
	}
}
