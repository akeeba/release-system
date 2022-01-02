<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Model\Mixin\CopyAware;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

class CategoryModel extends AdminModel
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
	protected $batch_commands = [
		'assetgroup_id' => 'batchAccess',
		'language_id'   => 'batchLanguage',
	];

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $formFactory);

		$this->_parent_table = '';
	}


	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_ars.category',
			'category',
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

	public function validate($form, $data, $group = null)
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user->authorise('core.admin', 'com_ars'))
		{
			if (isset($data['rules']))
			{
				unset($data['rules']);
			}
		}

		return parent::validate($form, $data, $group);
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_ars.edit.category.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			// Get the primary key of the record being edited.
			$pk = (int) $this->getState($this->getName() . '.id');

			// No primary key = new record. Override default values based on the filters set in the Categories page.
			if ($pk <= 0)
			{
				$data->title             = $app->getUserState('com_ars.categories.filter.search') ?: $data->title;
				$data->published         = $app->getUserState('com_ars.categories.filter.category_id') ?: $data->published;
				$data->show_unauth_links = $app->getUserState('com_ars.categories.filter.show_unauth_links') ?: $data->show_unauth_links;
				$data->is_supported      = $app->getUserState('com_ars.categories.filter.supported') ?: $data->is_supported;
				$data->access            = $app->getUserState('com_ars.categories.filter.access') ?: $data->access;
				$data->language          = $app->getUserState('com_ars.categories.filter.language') ?: $data->language;
			}
		}

		$this->preprocessData('com_ars.category', $data);

		return $data;
	}

	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = Factory::getApplication()->getIdentity();

		if (empty($table->getId()))
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
	 * @param   CategoryTable  $record
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

		// Make sure the user is allowed to delete this category, per Joomla's assets rules.
		$user = Factory::getApplication()->getIdentity();

		if (
			!$user->authorise('core.delete', 'com_ars.category.' . (int) $record->id) &&
			!$user->authorise('core.delete', 'com_ars')
		)
		{
			return false;
		}

		// Make sure there are no releases under this category
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('category_id') . ' = :cat_id')
			->bind(':cat_id', $record->id, ParameterType::INTEGER);

		try
		{
			$result = ($db->setQuery($query)->loadResult() ?: 0) == 0;
		}
		catch (Exception $e)
		{
			$result = true;
		}

		if (!$result)
		{
			$this->setError(Text::_('COM_ARS_CATEGORIES_NODELETE_VERSION'));
		}

		return $result;
	}
}