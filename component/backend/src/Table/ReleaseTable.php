<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableColumnAliasTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Database\DatabaseDriver;

/**
 * ARS Releases table
 *
 * @property int    $id                Primary key
 * @property int    $category_id       FK to #__ars_categories
 * @property string $version           Release title a.k.a. version
 * @property string $alias             Release alias for URL generation
 * @property string $maturity          Release maturity: 'alpha','beta','rc','stable'
 * @property string $notes             Release notes, displayed in frontend
 * @property string $hits              Hits (times displayed)
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 * @property int    $ordering          Front-end ordering
 * @property int    $access            Joomla view access level
 * @property int    $show_unauth_links Should I show unauthorized links?
 * @property string $redirect_unauth   Where should I redirect unauthorised access to?
 * @property int    $published         Publish state
 * @property string $language          Language code, '*' for all languages.
 */
class ReleaseTable extends AbstractTable
{
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use TableColumnAliasTrait;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = false;

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_releases', ['id'], $db);

		$this->setColumnAlias('catid', 'category_id');
		$this->setColumnAlias('title', 'version');

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
		$this->access     = 1;
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->category_id, 'COM_ARS_RELEASE_ERR_NEEDS_CATEGORY');
		$this->assertNotEmpty($this->version, 'COM_ARS_RELEASE_ERR_NEEDS_VERSION');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$this->alias = ApplicationHelper::stringURLSafe(strtolower($this->version));
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'COM_ARS_CATEGORY_ERR_NEEDS_SLUG');

		// Check alias for uniqueness
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('alias'),
				$db->quoteName('version'),
			])
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('category_id') . ' = :catid')
			->bind(':catid', $this->category_id);

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$existingItems = $db->setQuery($query)->loadAssocList('alias', 'version');

		$this->assertNotInArray($this->version, array_values($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_VERSION_UNIQUE');

		$this->assertNotInArray($this->alias, array_keys($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_ALIAS_UNIQUE');

		// Automatically fix the maturity
		if (!in_array($this->maturity, ['alpha', 'beta', 'rc', 'stable']))
		{
			$this->maturity = 'beta';
		}

		/**
		 * Filter the notes using a safe HTML filter.
		 *
		 * Yes, the form does filter the input BUT this table may be used outside the backend controller. This is an
		 * extra precaution to ensure we're not missing anything.
		 */
		if (!empty($this->notes))
		{
			$filter      = InputFilter::getInstance([], [], 1, 1);
			$this->notes = $filter->clean($this->notes);
		}

		// Set the default access level
		if ($this->access <= 0)
		{
			$this->access = 1;
		}

		// Clamp 'published' to [0, 1]
		$this->published = max(0, min($this->published, 1));

		// Make sure a non-empty ordering is set
		$this->ordering = $this->ordering ?? 0;
	}
}