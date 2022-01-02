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

class EnvironmentModel extends AdminModel
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

		$this->_parent_table = '';
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_ars.environment',
			'environment',
			[
				'control'   => 'jform',
				'load_data' => $loadData,
			]
		) ?: false;

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app->getUserState('com_ars.edit.environment.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			// Get the primary key of the record being edited.
			$pk = (int) $this->getState($this->getName() . '.id');

			// No primary key = new record.
			if ($pk <= 0)
			{
				$data->title = $app->getUserState('com_ars.environments.filter.search') ?: $data->title;
			}
		}

		$this->preprocessData('com_ars.environment', $data);

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
}