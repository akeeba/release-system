<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

use Akeeba\Component\ARS\Administrator\Mixin\AssertionAware;
use Akeeba\Component\ARS\Administrator\Table\Mixin\ColumnAliasAware;
use Akeeba\Component\ARS\Administrator\Table\Mixin\CreateModifyAware;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

/**
 * ARS Automatic item descriptions table
 *
 * @property int    $id                Primary key
 * @property int    $category          FK to #__ars_categories
 * @property string $packname          File / URL pattern match
 * @property string $title             Automatic title
 * @property string $description       Automatic description
 * @property string $environments      Comma-separated list of #__ars_environments IDs
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 * @property int    $published         Publish state
 */
class AutodescriptionTable extends AbstractTable
{
	use CreateModifyAware
	{
		CreateModifyAware::onBeforeStore as onBeforeStoreCreateModifyAware;
	}
	use AssertionAware;
	use ColumnAliasAware;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = false;

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_autoitemdesc', ['id'], $db);

		$this->setColumnAlias('catid', 'category');

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->category, 'COM_ARS_AUTODESCRIPTION_ERR_NEEDS_CATEGORY');
		$this->assertNotEmpty($this->packname, 'COM_ARS_AUTODESCRIPTION_ERR_NEEDS_PACKNAME');
		$this->assertNotEmpty($this->title, 'COM_ARS_AUTODESCRIPTION_ERR_NEEDS_TITLE');
		$this->assertNotEmpty($this->description, 'COM_ARS_AUTODESCRIPTION_ERR_NEEDS_DESCRIPTION');

		$category = new CategoryTable($this->getDbo());
		$this->assert($category->load($this->category) !== false, 'COM_ARS_AUTODESCRIPTION_ERR_NEEDS_CATEGORY_VALID');
	}

	protected function onBeforeStore(&$updateNulls)
	{
		$this->onBeforeStoreCreateModifyAware($updateNulls);

		if (is_array($this->environments))
		{
			$this->environments = json_encode($this->environments);
		}
	}

	protected function onAfterStore(&$result, &$updateNulls)
	{
		if (!is_array($this->environments))
		{
			$source             = $this->environments ?? '[]';
			$this->environments = @json_decode($source) ?: explode(',', $source) ?: [];
		}
	}

	protected function onBeforeBind(&$src, &$ignore = [])
	{
		if (is_object($src))
		{
			$src = (array) $src;
		}

		if (isset($src['environments']) && !is_array($src['environments']))
		{
			$source              = $src['environments'] ?? '[]';
			$src['environments'] = @json_decode($source) ?: explode(',', $source) ?: [];
		}
	}

}