<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\EnsureUcmTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Database\DatabaseDriver;

/**
 * ARS Categories table
 *
 * @property int    $id                      Primary key
 * @property int    $asset_id                Foreign key to #__assets
 * @property string $title                   Category title
 * @property string $alias                   Category alias for URL generation
 * @property string $description             Description (HTML)
 * @property string $type                    'normal' or 'bleedingedge'
 * @property string $directory               Relative directory
 * @property string $created                 Created date and time
 * @property int    $created_by              Created by this user
 * @property string $modified                Modified date and time
 * @property int    $modified_by             Modified by this user
 * @property int    $checked_out             Checked out by this user
 * @property string $checked_out_time        Checked out date and time
 * @property int    $ordering                Front-end ordering
 * @property int    $access                  Joomla view access level
 * @property int    $show_unauth_links       Should I show unauthorized links?
 * @property string $redirect_unauth         Where should I redirect unauthorised access to?
 * @property int    $published               Publish state
 * @property int    $is_supported            Is this software still supported?
 * @property string $language                Language code, '*' for all languages.
 *
 */
class CategoryTable extends AbstractTable implements TaggableTableInterface
{
	use TaggableTableTrait;
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use EnsureUcmTrait;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  7.0.0
	 */
	protected $_supportNullValue = false;

	/**
	 * Used internally by Joomla! to manage tags.
	 *
	 * @var   null|array
	 * @since 7.4.0
	 */
	public ?array $newTags;

	/**
	 * The UCM type alias. Used for tags, content versioning etc. Leave blank to effectively disable these features.
	 *
	 * @var    string
	 * @since  7.4.0
	 */
	public $typeAlias = 'com_ars.category';

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_categories', 'id', $db);

		$this->created_by = Factory::getApplication()->getIdentity()?->id;
		$this->created    = Factory::getDate()->toSql();
		$this->access     = 1;
	}

	/**
	 * Get the type alias for the tags mapping table
	 *
	 * The type alias generally is the internal component name with the content type. Ex.: com_content.article
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   7.4.0
	 */
	public function getTypeAlias(): string
	{
		return $this->typeAlias;
	}

	/**
	 * Runs after loading a record from the database
	 *
	 * @param   bool   $result  Did the record load?
	 * @param   mixed  $keys    The keys used to load the record.
	 * @param   bool   $reset   Was I asked to reset the object before loading the record?
	 *
	 * @return  void
	 *
	 * @since   7.4.0
	 */
	protected function onAfterLoad(bool &$result, $keys, bool $reset): void
	{
		// Make sure existing records have a UCM record
		if (!$result || !empty($this->id))
		{
			$this->ensureUcmRecord();
		}
	}

	public function bind($src, $ignore = [])
	{
		// Bind the rules.
		if (isset($src['rules']) && is_array($src['rules']))
		{
			$rules = new Rules($src['rules']);
			$this->setRules($rules);
		}

		return parent::bind($src, $ignore);
	}

	public function _getAssetName()
	{
		return 'com_ars.category.' . $this->id;
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->title, 'COM_ARS_CATEGORY_ERR_NEEDS_TITLE');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$this->alias = ApplicationHelper::stringURLSafe(strtolower($this->title));
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'COM_ARS_CATEGORY_ERR_NEEDS_SLUG');

		// Check alias for uniqueness
		$db    = $this->getDbo();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('alias'))
			->from($db->quoteName('#__ars_categories'));

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$aliases = $db->setQuery($query)->loadColumn();

		$this->assertNotInArray($this->alias, $aliases, 'COM_ARS_CATEGORY_ERR_NEEDS_UNIQUE_SLUG');

		// Check directory
		$this->directory = rtrim($this->directory, '/');

		$check = trim($this->directory);

		$this->assertNotEmpty($check, 'COM_ARS_CATEGORY_ERR_NEEDS_DIRECTORY');

		$directory = JPATH_SITE . '/' . $this->directory;

		try
		{
			$folderExists = Folder::exists($directory);
		}
		catch (\Exception $e)
		{
			$folderExists = false;
		}

		$this->assert($folderExists, 'COM_ARS_CATEGORY_ERR_DIRECTORY_NOT_EXISTS');

		// Automatically fix the type
		if (!in_array($this->type, ['normal', 'bleedingedge']))
		{
			$this->type = 'normal';
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

	protected function _getAssetTitle()
	{
		return $this->title;
	}

	protected function _getAssetParentId(?Table $table = null, $id = null)
	{
		/** @var Asset $asset */
		$asset = new Asset($this->getDbo(), $this->getDispatcher());
		$asset->loadByName('com_ars');
		$assetId = $asset->id;

		return !empty($assetId) ? $assetId : parent::_getAssetParentId($table, $id);
	}
}
