<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\Mixin\CopyAware;
use Akeeba\Component\ARS\Administrator\Table\DlidlabelTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model\BeforeBatchEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class DlidlabelModel extends AdminModel
{
	use CopyAware;

	/**
	 * Batch copy/move command. If set to false, the batch copy/move command is not supported
	 *
	 * @var    string
	 * @since  7.0
	 */
	protected $batch_copymove = false;

	/**
	 * Allowed batch commands
	 *
	 * @var  array
	 */
	protected $batch_commands = [];

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);

		$this->_parent_table = null;
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
			'com_ars.dlidlabel',
			'dlidlabel',
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

		// Modify the form based on access controls.
		$canEditState = $this->canEditState((object) $item);
		$user         = Factory::getApplication()->getIdentity();
		$canEditUser  = $user->authorise('core.admin', $this->option);

		if (!$canEditState)
		{
			$form->setFieldAttribute('published', 'disabled', 'true');
			$form->setFieldAttribute('published', 'required', 'false');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		if (!$canEditUser)
		{
			$form->setFieldAttribute('user_id', 'disabled', 'true');
			$form->setFieldAttribute('user_id', 'readonly', 'true');
			$form->setFieldAttribute('user_id', 'class', 'readonly');
			$form->setFieldAttribute('user_id', 'filter', 'unset');
		}

		if (empty($id))
		{
			$form->setFieldAttribute('dlid', 'description', 'COM_ARS_DLIDLABELS_FIELD_DLID_HELP_NEW');
		}

		if ($data['primary'] ?? $form->getValue('primary') ?? 0)
		{
			$form->setFieldAttribute('title', 'disabled', 'true');
			$form->setFieldAttribute('title', 'readonly', 'true');
			$form->setFieldAttribute('title', 'class', 'readonly');
			$form->setFieldAttribute('title', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to reset the Download IDs of one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   7.0.0
	 */
	public function reset(&$pks)
	{
		$pks = ArrayHelper::toInteger((array) $pks);

		/** @var DlidlabelTable $table */
		$table = $this->getTable();

		// Iterate the items to reset each one.
		foreach ($pks as $i => $pk)
		{
			if (!$table->load($pk))
			{
				$this->setError($table->getError());

				return false;
			}

			if (!$this->canEditState($table))
			{
				// Prune items that you can't change.
				unset($pks[$i]);

				$error = $this->getError();

				if ($error)
				{
					Log::add($error, Log::WARNING, 'jerror');

					return false;
				}

				Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');

				return false;
			}

			if (!$table->save([
				/**
				 * IMPORTANT! Do NOT use NULL. NULLs are ignored. An empty string is not and causes the Table to
				 * regenerate the Download ID to satisfy the restrictions we are placing on the column during check().
				 */
				'dlid' => '',
			]))
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
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
		$data = $app->getUserState('com_ars.edit.dlidlabel.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			$pk = (int) $this->getState($this->getName() . '.id');

			// No primary key = new record.
			if ($pk <= 0)
			{
				$data->published = 1;
			}
		}

		$this->preprocessData('com_ars.dlidlabel', $data);

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
		$user = Factory::getApplication()->getIdentity();

		if ($record->user_id == $user->id)
		{
			return true;
		}

		return parent::canDelete($record);
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
		$user = Factory::getApplication()->getIdentity();

		if ($record->user_id == $user->id)
		{
			return true;
		}

		return parent::canEditState($record);
	}
}