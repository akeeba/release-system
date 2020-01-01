<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\DataController;
use FOF30\Controller\Exception\ItemNotFound;
use FOF30\Controller\Exception\TaskNotFound;
use Joomla\CMS\Language\Text;

class DownloadIDLabels extends DataController
{
	/**
	 * Executes a given controller task. The onBefore<task> and onAfter<task> methods are called automatically if they
	 * exist.
	 *
	 * If $task == 'default' we will determine the CRUD task to use based on the view name and HTTP verb in the request,
	 * overriding the routing.
	 *
	 * @param   string $task The task to execute, e.g. "browse"
	 *
	 * @return  null|bool  False on execution failure
	 *
	 * @throws  TaskNotFound  When the task is not found
	 */
	public function execute($task): ?bool
	{
		if ($this->container->platform->isFrontend())
		{
			$user = $this->container->platform->getUser();

			if (($user->id <= 0) || $user->guest)
			{
				throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}

			$this->input->set('user_id', $user->id);
		}

		return parent::execute($task);
	}

	/**
	 * Reset the default Download ID
	 *
	 * @throws \Exception
	 */
	public function reset(): void
	{
		$this->csrfProtection();

		/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $model */
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

		try
		{
			$model->dlid = null;
			$model->save();
			$error = null;
		}
		catch (\Exception $e)
		{
			$error = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->get('returnurl', '', 'string'))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=' . $this->container->componentName . '&view=' . $this->container->inflector->pluralize($this->view) . $this->getItemidURLSuffix();

		if (!is_null($error))
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	protected function onBeforeBrowse(): bool
	{
		if ($this->container->platform->isFrontend() && $this->container->platform->getUser()->guest)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		return true;
	}

	protected function onBeforeAdd(): bool
	{
		if ($this->container->platform->isFrontend())
		{
			$this->layout = 'form';
		}

		return true;
	}

	/**
	 * Edit view permissions check. Overridden to make sure a user won't try
	 * editing another user's add-on Download IDs.
	 *
	 * @return  bool
	 *
	 * @throws  \RuntimeException
	 */
	protected function onBeforeEdit(): bool
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $model */
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

		if ($model->primary)
		{
			throw new \RuntimeException(Text::_('COM_ARS_DLIDLABELS_ERR_CANTEDITDEFAULT'), 403);
		}

		if (!$this->container->platform->isFrontend())
		{
			return true;
		}

		if ($model->user_id != $this->container->platform->getUser()->id)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		$this->layout = 'form';

		return true;
	}

	/**
	 * Edit view permissions check. Overridden to make sure a user won't try
	 * editing another user's add-on Download IDs.
	 *
	 * @return  bool
	 *
	 * @throws  \RuntimeException
	 */
	protected function onBeforeReset(): bool
	{
		if (!$this->container->platform->isFrontend())
		{
			return true;
		}

		/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $model */
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

		if ($model->user_id != $this->container->platform->getUser()->id)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		$this->layout = 'form';

		return true;
	}

	/**
	 * Read view permissions check. Overridden to make sure a user won't try
	 * reading another user's add-on Download IDs.
	 *
	 * @return  bool
	 *
	 * @throws  \RuntimeException
	 */
	protected function onBeforeRead(): bool
	{
		return $this->onBeforeReset();
	}

	protected function onBeforeCancel(): bool
	{
		if ($this->container->platform->getUser()->guest)
		{
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		return true;
	}

	protected function onBeforeSave(): bool
	{
		$this->getModel()->savestate(0);

		if ($this->container->platform->isFrontend())
		{
			$user = $this->container->platform->getUser();

			/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $model */
			$model = $this->getModel()->savestate(false);

			if (empty($model->user_id) && empty($model->id))
			{
				$model->user_id = $user->id;
			}
		}

		return $this->onBeforeReset();
	}

	protected function onBeforePublish(): bool
	{
		return $this->onBeforeEdit();
	}

	protected function onBeforeUnpublish(): bool
	{
		return $this->onBeforeEdit();
	}

	protected function onBeforeRemove(): bool
	{
		return $this->onBeforeEdit();
	}
}
