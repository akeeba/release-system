<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ModelCopyTrait;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Utilities\ArrayHelper;

#[\AllowDynamicProperties]
class AutodescriptionModel extends AdminModel
{
	use ModelCopyTrait;

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);

		$this->_parent_table = 'Category';
	}

	public function copy($pks)
	{
		$table = $this->getTable();
		$ret   = [];

		foreach ($pks as $pk)
		{
			$table->reset();

			if (!$table->load($pk))
			{
				continue;
			}

			$table->id = 0;

			if ($table->store())
			{
				$ret[$pk] = $table->getId();
			}
		}

		return $ret;
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
			'com_ars.autodescription',
			'autodescription',
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
		$data = $app->getUserState('com_ars.edit.autodescription.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			// Get the primary key of the record being edited.
			$pk = (int) $this->getState($this->getName() . '.id');

			// No primary key = new record. Override default values based on the filters set in the Auto Descriptions page.
			if ($pk <= 0)
			{
				$data->title    = $app->getUserState('com_ars.autodescriptions.filter.search') ?: $data->title;
				$data->category = $app->getUserState('com_ars.autodescriptions.filter.category_id') ?: $data->category;
			}
			else
			{
				// Joomla stupidly converts the array of environments to a CMSObject its own form fields can't read...
				$data->environments = ArrayHelper::fromObject($data->environments);
			}
		}

		$this->preprocessData('com_ars.autodescription', $data);

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

}