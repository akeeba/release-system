<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\Mixin\CopyAware;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model\BeforeBatchEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;

class ItemModel extends AdminModel
{
	use CopyAware;

	/**
	 * Batch copy/move command. If set to false, the batch copy/move command is not supported
	 *
	 * @var    string
	 * @since  7.0
	 */
	protected $batch_copymove = 'release_id';

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

		$this->_parent_table = 'Release';
	}

	/**
	 * Override batch processing to add custom onBeforeBatch event handler.
	 *
	 * @param   array  $commands
	 * @param   array  $pks
	 * @param   array  $contexts
	 *
	 * @return  bool
	 * @throws  Exception
	 * @see     ReleaseModel::batch()
	 *
	 * @since   7.0.0
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

	/**
	 * Applies custom ACL during batch processing of records.
	 *
	 * @param   BeforeBatchEvent  $event  The event to handle
	 *
	 * @return  void
	 * @throws  Exception
	 * @see     self::batch
	 * @since   7.0.0
	 */
	public function onBeforeBatch(BeforeBatchEvent $event)
	{
		$table = $event->getArgument('src');
		$type  = $event->getArgument('type');

		if (!is_object($table) || !($table instanceof ItemTable))
		{
			return;
		}

		// Let's get the Release so we can figure out what is the Category we belong to
		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load($table->release_id))
		{
			return;
		}

		$user = Factory::getApplication()->getIdentity();

		switch ($type)
		{
			// Copy: we must be allowed to create items in the category
			case 'copy':
				if (!$user->authorise('core.create', 'com_ars.category.' . $release->category_id))
				{
					throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));
				}
				break;

			// Move, access, language etc: we must be allowed to edit items in the category
			default:
				if (!$user->authorise('core.edit', 'com_ars.category.' . $release->category_id))
				{
					throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
				}
				break;
		}
	}

	/**
	 * Get the add/edit form.
	 *
	 * This is responsible for enabling, disabling or removing fields based on the access control preferences.
	 *
	 * @param   array  $data
	 * @param   bool   $loadData
	 *
	 * @return false|Form
	 * @throws Exception
	 * @since  7.0.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_ars.item',
			'item',
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
			$form->setFieldAttribute('published', 'disabled', 'true');
			$form->setFieldAttribute('published', 'required', 'false');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	protected function getReorderConditions($table)
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();

		$where = [];

		$fltRelease   = $app->getUserState('com_ars.items.filter.release_id');
		$fltPublished = $app->getUserState('com_ars.items.filter.published');

		$db = $this->getDbo();

		if (is_numeric($fltRelease))
		{
			$where[] = $db->quoteName('release_id') . ' = ' . $db->quote((int) $fltRelease);
		}

		if (is_numeric($fltPublished))
		{
			$where[] = $db->quoteName('published') . ' = ' . $db->quote((int) $fltPublished);
		}

		return $where;
	}

	/**
	 * Load the data of an add / edit form.
	 *
	 * The data is loaded from the user state. If the user state is empty we load the item being edited. If there is no
	 * item being edited we will override the default table values with the respective list filter values. This makes
	 * sense for users. If I am filtering by category X and maturity Stable I am probably trying to see if there is a
	 * specific stable version released in category X and, if not, create it. Using the filter values reduces the
	 * possibility for silly mistakes on the part of the operator.
	 *
	 * @return array|bool|\Joomla\CMS\Object\CMSObject|mixed
	 * @throws Exception
	 */
	protected function loadFormData()
	{
		/** @var CMSApplication $app */
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_ars.edit.item.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			// Get the primary key of the record being edited.
			$pk = (int) $this->getState($this->getName() . '.id');

			// No primary key = new record. Override default values based on the filters set in the Items page.
			if ($pk <= 0)
			{
				$data->title             = $app->getUserState('com_ars.items.filter.search') ?: $data->title;
				$data->release_id        = $app->getUserState('com_ars.items.filter.category_id') ?: $data->release_id;
				$data->published         = $app->getUserState('com_ars.items.filter.published') ?: $data->published;
				$data->show_unauth_links = $app->getUserState('com_ars.items.filter.show_unauth_links') ?: $data->show_unauth_links;
				$data->access            = $app->getUserState('com_ars.items.filter.access') ?: $data->access;
				$data->language          = $app->getUserState('com_ars.items.filter.language') ?: $data->language;
			}
		}

		$this->preprocessData('com_ars.item', $data);

		return $data;
	}

	protected function prepareTable($table)
	{
		// Set up the created / modified date
		$date  = Factory::getDate();
		$user  = Factory::getApplication()->getIdentity();
		$isNew = empty($table->getId());

		if ($isNew)
		{
			// Set the values
			$table->created    = $date->toSql();
			$table->created_by = $user->id;
		}
		else
		{
			// Set the values
			$table->modified    = $date->toSql();
			$table->modified_by = $user->id;
		}
	}

	/**
	 * @param   ReleaseTable|object  $record
	 *
	 * @return  bool
	 * @throws  Exception
	 */
	protected function canDelete($record): bool
	{
		// We can't delete an empty record with no ID!
		if (empty($record->id))
		{
			return false;
		}

		// Make sure the user is allowed to delete this release, per Joomla's assets rules for its parent category.
		$user = Factory::getApplication()->getIdentity();

		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load($record->release_id))
		{
			return parent::canDelete($record);
		}

		if (
			!$user->authorise('core.delete', 'com_ars.category.' . (int) $release->category_id) &&
			!$user->authorise('core.delete', 'com_ars')
		)
		{
			return false;
		}

		return true;
	}

	/**
	 * Is the user allowed to change the item state?
	 *
	 * Since a release belongs to a category which belongs to the component we check whether the user has the
	 * core.edit.state privilege in the category itself.
	 *
	 * @param   ReleaseTable|object  $record
	 *
	 * @return  bool
	 * @throws  Exception
	 */
	protected function canEditState($record)
	{
		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load($record->release_id))
		{
			return parent::canEditState($record);
		}

		// Make sure the user is allowed to delete this release, per Joomla's assets rules for its parent category.
		$user = Factory::getApplication()->getIdentity();

		if (
			!$user->authorise('core.edit.state', 'com_ars.category.' . (int) $release->category_id) &&
			!$user->authorise('core.edit.state', 'com_ars')
		)
		{
			return false;
		}

		return true;
	}

	/**
	 * Validate the form data.
	 *
	 * Overridden to allow the multiselect 'environments' list to have no items selected. In this case there is no value
	 * returned by the form which means it can never be unset. We catch that and force it to an empty array.
	 *
	 * @param   Form   $form
	 * @param   array  $data
	 * @param   null   $group
	 *
	 * @return array|bool
	 */
	public function validate($form, $data, $group = null)
	{
		$validData = parent::validate($form, $data, $group);

		if ($validData === false)
		{
			return $validData;
		}

		if (!isset($validData['environments']))
		{
			$validData['environments'] = [];
		}

		return $validData;
	}


}