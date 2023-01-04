<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

trait ControllerCopyTrait
{
	use ControllerEvents;

	/**
	 * Method to copy (duplicate) a list of items.
	 *
	 * @return  void
	 *
	 * @since   7.0
	 */
	public function copy()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get items to publish from the request.
		$cid = $this->input->get('cid', [], 'array');

		if (empty($cid))
		{
			$this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), ['category' => 'jerror']);

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list
					. $this->getRedirectToListAppend(), false
				)
			);

			return;
		}

		// Get the model.
		$model = $this->getModel();

		// Make sure the item ids are integers
		$cid = ArrayHelper::toInteger($cid);

		$this->triggerEvent('onBeforeCopy', [&$cid]);

		// Publish the items.
		try
		{
			$copyMap       = $model->copy($cid) ?: [];
			$errors        = $model->getErrors();
			$copiedSuccess = count($copyMap);
			$copiedFailed  = count($cid) - $copiedSuccess;
			$app           = Factory::getApplication();

			if (count($errors))
			{
				foreach ($errors as $error)
				{
					$app->enqueueMessage($error, 'error');
				}
			}

			if ($copiedFailed > 0)
			{
				$app->enqueueMessage(Text::plural($this->text_prefix . '_N_ITEMS_FAILED_COPY', $copiedFailed), 'error');
			}

			if ($copiedSuccess > 0)
			{
				$this->setMessage(Text::plural($this->text_prefix . '_N_ITEMS_COPIED', \count($cid)));
			}
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->triggerEvent('onAfterCopy', [&$cid, &$copyMap]);

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(), false
			)
		);
	}

}