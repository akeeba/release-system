<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model\Mixin;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;

/**
 * Trait to modify batchCopy for relations involving parent tables OTHER than the Joomla core categories table.
 */
trait CopyAware
{
	/**
	 * MVC table name this records belongs to (as the leaf node of an one-to-many relation).
	 *
	 * Use "_core_categories" to use Joomla's core categories.
	 *
	 * Use null for records without parents. In this case batchCopy() clones records, modifying the title and alias.
	 *
	 * @var string
	 *
	 * @since   7.0.0
	 */
	protected $_parent_table = '_core_categories';

	public function copy($pks)
	{
		return $this->batchCopy(0, $pks, []);
	}

	/**
	 * Method to check the validity of the parent table ID for batch copy and move
	 *
	 * @param   integer  $categoryId  The parent table ID to check
	 *
	 * @return  boolean
	 *
	 * @since   7.0.0
	 */
	protected function checkCategoryId($categoryId)
	{
		if ($this->_parent_table === '_core_categories')
		{
			return parent::checkCategoryId($categoryId);
		}

		// If there is no parent table only accept an empty parent table ID
		if (empty($this->_parent_table))
		{
			return empty($categoryId);
		}

		if (empty($categoryId))
		{
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));

			return false;
		}

		// Check that the category exists
		$categoryTable = $this->getMVCFactory()->createTable($this->_parent_table, 'Administrator');

		if (!$categoryTable->load($categoryId))
		{
			$this->setError(
				$categoryTable->getError() ?:
					Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND')
			);

			return false;
		}

		// Check that the user has create permission for the component
		$extension = Factory::getApplication()->input->get('option', '');
		$user      = Factory::getApplication()->getIdentity();

		// If the parent table has no asset I will only check if I can create items in the component
		if (!$categoryTable->hasField('asset_id'))
		{
			if (!$user->authorise('core.create', $extension))
			{
				$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));

				return false;
			}

			return true;
		}

		// The parent table has an asset. Let's check if the user is allowed to create items in it.
		if (!$user->authorise('core.create', $categoryTable->getAssetName()))
		{
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));

			return false;
		}

		return true;
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $categoryId  The id of the category.
	 * @param   string   $alias       The alias.
	 * @param   string   $title       The title.
	 *
	 * @return    array  Contains the modified title and alias.
	 *
	 * @since    7.0.0
	 */
	protected function generateNewTitle($categoryId, $alias, $title)
	{
		if ($this->_parent_table === '_core_categories')
		{
			return parent::generateNewTitle($categoryId, $alias, $title);
		}

		$table = $this->getTable();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName($table->getTableName()));

		$hasAlias   = $table->hasField('alias');
		$aliasField = $table->getColumnAlias('alias');
		$hasCatID   = $table->hasField('catid');
		$catidField = $table->getColumnAlias('catid');
		$hasTitle   = $table->hasField('title');
		$titleField = $table->getColumnAlias('title');

		if ($hasAlias)
		{
			$query->where($db->quoteName($aliasField) . ' = :alias')
				->bind(':alias', $alias);
		}

		if ($hasCatID)
		{
			$query->where($db->quoteName($catidField) . ' = :catid')
				->bind(':catid', $categoryId);
		}

		while ($rawData = $db->setQuery($query)->loadAssoc() ?: '')
		{
			$table->reset();
			$table->bind($rawData);

			if ($hasTitle && ($title === $table->$titleField))
			{
				$title = StringHelper::increment($title);
			}

			if ($hasAlias)
			{
				$alias = StringHelper::increment($alias, 'dash');
				$query->unbind(':alias');
				$query->bind(':alias', $alias);
			}
			else
			{
				break;
			}
		}

		return [$title, $alias];
	}

}