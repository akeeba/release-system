<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\Mixin\CopyAware;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Joomla\CMS\Event\Model\BeforeBatchEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

class ReleaseModel extends AdminModel
{
	use CopyAware;

	/**
	 * Batch copy/move command. If set to false, the batch copy/move command is not supported
	 *
	 * @var    string
	 * @since  7.0
	 */
	protected $batch_copymove = 'category_id';

	/**
	 * Allowed batch commands
	 *
	 * @var  array
	 */
	protected $batch_commands = [
		'assetgroup_id' => 'batchAccess',
		'language_id'   => 'batchLanguage',
	];

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);

		$this->_parent_table = 'Category';
	}

	/**
	 * Override batch processing to add custom onBeforeBatch event handler.
	 *
	 * Joomla assumes that all items being batch processed are assets. This means it will check com_ars.release.123 for
	 * permissions to edit or create an item (depending on the batch command). However, Releases are not assets. The
	 * permissions are defined by the parent category. We would need to either fork all batch operations (way too much
	 * overhead) or use the onBeforeBatch event.
	 *
	 * The latter is the ideal method but it normally has to be registered by plugins. Instead of requiring a plugin to
	 * enforce a security feature we instead register an event listener for the duration of the batch processing
	 * operation.
	 *
	 * Furthermore, the event listener cannot return any data (it's an immutable event the onBeforeBatch event we're
	 * handling) therefore we wrap it with a try/catch which treats any RuntimeException thrown by the event handler as
	 * a model error. The finally block removes the temporary listener regardless of the outcome, undoing the changes we
	 * made to the application's event dispatcher.
	 *
	 * Back n August 2016 I had contributed the code in Joomla 4 which converted all internal event handlers to events
	 * and had them go through the application's event dispatcher. This code here shows you one of the many reasons this
	 * is important and how you can use it in real world software to work around restrictions in Joomla's core code
	 * without forking the code and creating an unmaintainable mess. This is something we had been doing in FOF 3 since
	 * 2015. You're welcome :)
	 *
	 * @param   array  $commands
	 * @param   array  $pks
	 * @param   array  $contexts
	 *
	 * @return  bool
	 * @throws  \Exception
	 */
	public function batch($commands, $pks, $contexts)
	{
		$dispatcher = Factory::getApplication()->getDispatcher();
		$dispatcher->addListener('onBeforeBatch', [$this, 'onBeforeBatch']);

		try
		{
			return parent::batch($commands, $pks, $contexts);
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
		finally
		{
			$dispatcher->removeListener('onBeforeBatch', [$this, 'onBeforeBatch']);
		}
	}

	public function onBeforeBatch(BeforeBatchEvent $event)
	{
		$table = $event->getArgument('src');
		$type = $event->getArgument('type');

		if (!is_object($table) || !($table instanceof ReleaseTable))
		{
			return;
		}

		$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();

		switch ($type)
		{
			// Copy: we must be allowed to create items in the category
			case 'copy':
				if (!$user->authorise('core.create', 'com_ars.category.' . $table->category_id))
				{
					throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));
				}
				break;

			// Move, access, language etc: we must be allowed to edit items in the category
			default:
				if (!$user->authorise('core.edit', 'com_ars.category.' . $table->category_id))
				{
					throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
				}
				break;

		}
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_ars.release',
			'release',
			[
				'control'   => 'jform',
				'load_data' => $loadData,
			]
		) ?: false;

		if (empty($form))
		{
			return false;
		}

		$id = $data['id'] ?? $form->getValue('id');

		$item = $this->getItem($id);

		$canEditState = $this->canEditState((object) $item);

		// Modify the form based on access controls.
		if (!$canEditState)
		{
			if (!$canEditState)
			{
				$form->setFieldAttribute('published', 'disabled', 'true');
				$form->setFieldAttribute('published', 'required', 'false');
				$form->setFieldAttribute('published', 'filter', 'unset');
			}
		}

		return $form;
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_ars.edit.release.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_ars.release', $data);

		return $data;
	}

	protected function prepareTable($table)
	{
		/**
		 * Access check. This is called from save().
		 *
		 * The release belongs to a category. The user needs to be authorized for core.create, core.edit or
		 * core.edit.own (depending on the table's state) to save data into the table.
		 */
		$user   = Factory::getApplication()->getIdentity() ?: Factory::getUser();
		$isNew  = !empty($table->getId());
		$isOwn  = !$isNew && ($table->created_by == $user->id);
		$asset  = 'com_ars.category.' . $table->category_id;
		$action = $isNew ? 'core.create' : ($isOwn ? 'core.edit.own' : 'core.edit');

		if (!$user->authorise($action, $asset) && !$user->authorise($action, 'com_ars'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Set up the created / modified date
		$date = Factory::getDate();

		if ($isNew)
		{
			// Set the values
			$table->created_on = $date->toSql();
			$table->created_by = $user->id;
		}
		else
		{
			// Set the values
			$table->modified_on = $date->toSql();
			$table->modified_by = $user->id;
		}
	}

	/**
	 * @param   ReleaseTable  $record
	 *
	 * @return  bool
	 * @throws  \Exception
	 */
	protected function canDelete($record): bool
	{
		// We can't delete an empty record with no ID!
		if (empty($record->id))
		{
			return false;
		}

		// Make sure the user is allowed to delete this release, per Joomla's assets rules for its parent category.
		$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();

		if (
			!$user->authorise('core.delete', 'com_ars.category.' . (int) $record->category_id) &&
			!$user->authorise('core.delete', 'com_ars')
		)
		{
			return false;
		}

		// Make sure there are no items under this releases
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__ars_items'))
			->where($db->quoteName('release_id') . ' = :release_id')
			->bind(':release_id', $record->id, ParameterType::INTEGER);

		try
		{
			$result = ($db->setQuery($query)->loadResult() ?: 0) == 0;
		}
		catch (\Exception $e)
		{
			$result = true;
		}

		if (!$result)
		{
			$this->setError(Text::_('COM_ARS_CATEGORIES_NODELETE_VERSION'));
		}

		return $result;
	}

	/**
	 * Is the user allowed to change the item state?
	 *
	 * Since a release belongs to a category which belongs to the component we check whether the user has the
	 * core.edit.state privilege in the category itself.
	 *
	 * @param   ReleaseTable  $record
	 *
	 * @return  bool
	 * @throws  \Exception
	 */
	protected function canEditState($record)
	{
		// Make sure the user is allowed to delete this release, per Joomla's assets rules for its parent category.
		$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();

		if (
			!$user->authorise('core.edit.state', 'com_ars.category.' . (int) $record->category_id) &&
			!$user->authorise('core.edit.state', 'com_ars')
		)
		{
			return false;
		}

		return true;
	}
}