<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\Controller\Exception\ItemNotFound;

class Item extends DataController
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		if ($this->getModel()->getState('limit', 0) != 0)
		{
			$this->getModel()->with([]);
		}
	}

	protected function onBeforeApplySave(&$data)
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

				throw new \RuntimeException(\JText::_($message), 403);
			}
		}

		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}

		// Save as above *sigh*
		if (!isset($data['environments']))
		{
			$data['environments'] = array();
		}
	}

	protected function onBeforeAdd()
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
				$this->defaultsForAdd[ $k ] = $stateValue;
			}
		}
	}

	protected function onBeforeDelete()
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
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.delete', $this->container->componentName . '.category.' . $model->release->category_id))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}
	}

	protected function onBeforeEdit()
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
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		if (!$this->container->platform->getUser()->authorise('core.edit', $this->container->componentName . '.category.' . $model->release->category_id))
		{
			throw new \RuntimeException(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}
	}
}
