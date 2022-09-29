<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\AssertionAware;
use Akeeba\Component\ARS\Administrator\Table\Mixin\ColumnAliasAware;
use Akeeba\Component\ARS\Administrator\Table\Mixin\CreateModifyAware;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\Database\DatabaseDriver;

/**
 * ARS Items table
 *
 * @property int    $id                Primary key
 * @property int    $release_id        FK to #__ars_categories
 * @property string $title             Item title
 * @property string $alias             Item URL alias
 * @property string $description       Description (HTML)
 * @property string $type              Item type: 'link','file'
 * @property string $filename          Relative file path to category folder, when type='file'
 * @property string $url               Absolute URL to file, when type='link'
 * @property int    $updatestream      FK to #__ars_updatestreams
 * @property string $md5               MD5 sum for the download item
 * @property string $sha1              SHA-1  sum for the download item
 * @property string $sha256            SHA-256 sum for the download item
 * @property string $sha384            SHA-384 sum for the download item
 * @property string $sha512            SHA-512 sum for the download item
 * @property int    $filesize          Download file size, in bytes
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
 * @property string $environments      Comma-separated list of #__ars_environments IDs
 */
class ItemTable extends AbstractTable
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
		parent::__construct('#__ars_items', ['id'], $db);

		$this->setColumnAlias('catid', 'release_id');

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
		$this->access     = 1;
	}

	protected function onBeforeCheck()
	{
		// We need a category
		$this->assertNotEmpty($this->release_id, 'COM_ARS_ITEM_ERR_NEEDS_CATEGORY');

		// We need a filetype
		$this->assertInArray($this->type, ['link', 'file'], 'COM_ARS_ITEM_ERR_NEEDS_TYPE');

		// We need a file name or URL, depending on the item type
		switch ($this->type)
		{
			case 'file':
				$this->assertNotEmpty($this->filename, 'COM_ARS_ITEM_ERR_NEEDS_FILENAME');
				$this->url = '';
				break;

			case 'link':
				$this->assertNotEmpty($this->url, 'COM_ARS_ITEM_ERR_NEEDS_LINK');
				$this->filename = '';
				break;
		}

		// Get the title and aliases of other items in the same release
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->qn('title'),
				$db->qn('alias'),
			])->from($db->qn('#__ars_items'))
			->where($db->qn('release_id') . ' = :release_id')
			->bind(':release_id', $this->release_id);

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$info    = $db->setQuery($query)->loadAssocList('title', 'alias') ?: [];
		$titles  = array_keys($info);
		$aliases = array_keys($info);
		unset($info);

		// Let's get automatic item title/description records
		$this->applyAutoDescriptions();

		// Filter out empty environments
		if (!empty($this->environments) && is_array($this->environments))
		{
			$this->environments = array_filter($this->environments, function ($x) {
				return !empty($x);
			});
		}

		// Set up a title from the filename if no title is specified
		$this->title = $this->title ?: basename(($this->type === 'file') ? ($this->filename ?? '') : ($this->url ?? ''));

		$this->assertNotEmpty($this->title, 'COM_ARS_ITEM_ERR_NEEDS_TITLE');
		$this->assertNotInArray($this->title, $titles, 'COM_ARS_ITEM_ERR_NEEDS_TITLE_UNIQUE');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$filename  = basename(($this->type === 'file') ? ($this->filename ?? '') : ($this->url ?? ''));
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$filename  = pathinfo($filename, PATHINFO_FILENAME);

			$this->alias = ApplicationHelper::stringURLSafe($filename) .
				(empty($extension) ? '' : ('-' . ApplicationHelper::stringURLSafe($extension)));
		}

		$this->assertNotEmpty($this->alias, 'COM_ARS_ITEM_ERR_NEEDS_ALIAS');
		$this->assertNotInArray($this->alias, $aliases, 'COM_ARS_ITEM_ERR_NEEDS_ALIAS_UNIQUE');

		// Filter the description using a safe HTML filter
		$filter            = InputFilter::getInstance([], [], 1, 1);
		$this->description = $this->description ? $filter->clean($this->description) : '';

		// Set a default access
		$this->access = ($this->access <= 0) ? 1 : $this->access;

		// If the publish state is an empty string, null or 0 set it to integer zero, please.
		$this->published = $this->published ?: 0;

		// Apply an update stream, if possible
		$this->updatestream = $this->updatestream ?: $this->getUpdateStream();

		// Update the file size and / or file hashes if they are not already present.
		if (empty($this->md5) || empty($this->sha1) || empty($this->sha256) || empty($this->sha384) || empty($this->sha512) || empty($this->filesize))
		{
			$filename = null;

			if (($this->type == 'file') && !empty($this->filename))
			{
				$folder  = null;
				$release = new ReleaseTable($this->getDbo());

				if ($release->load($this->release_id))
				{
					$category = new CategoryTable($this->getDbo());

					if ($category->load($release->category_id))
					{
						$folder = $category->directory;
					}
				}

				if (!empty($folder))
				{
					$folder = JPATH_ROOT . '/' . $folder;

					if (!Folder::exists($folder))
					{
						$folder = null;
					}

					if (!empty($folder))
					{
						$filename = $folder . '/' . $this->filename;
					}
				}
			}

			if (($this->type == 'link') || !empty($this->url))
			{
				$target = Factory::getApplication()->get('tmp_path') . '/temp.dat';
				InstallerHelper::downloadPackage($this->url, $target);
				$filename = $target;
			}

			if (!empty($filename) && (!@file_exists($filename) || !@is_file($filename)))
			{
				$filename = null;
			}

			if (!empty($filename))
			{
				$this->md5      = $this->md5 ?: hash_file('md5', $filename);
				$this->sha1     = $this->sha1 ?: hash_file('sha1', $filename);
				$this->sha256   = $this->sha256 ?: hash_file('sha256', $filename);
				$this->sha384   = $this->sha384 ?: hash_file('sha384', $filename);
				$this->sha512   = $this->sha512 ?: hash_file('sha512', $filename);
				$this->filesize = $this->filesize ?: (@filesize($filename) ?: 0);
			}

			if (!empty($filename) && ($this->type == 'link'))
			{
				$dummy = @unlink($filename) || File::delete($filename);
			}
		}

		// Make sure a non-empty ordering is set
		$this->ordering = $this->ordering ?? 0;
	}

	/**
	 * Uses any applicable automatic description record to update missing information in this item.
	 *
	 * It will update the environments, title and description if they are missing.
	 *
	 * @return  void
	 */
	protected function applyAutoDescriptions(): void
	{
		// Get the applicable automatic description records matching the release's category
		$db = $this->getDbo();

		$subQuery = $db->getQuery(true)
			->select($db->quoteName('category_id'))
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('id') . ' = ' . $db->q($this->release_id));

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_autoitemdesc'))
			->where($db->quoteName('category') . ' IN (' . $subQuery . ')')
			->where($db->quoteName('published') . ' != 0')
			->order($db->quoteName('id') . ' ASC');

		$autoItems = $db->setQuery($query)->loadObjectList() ?: [];

		// If there are no items bail out
		if (empty($autoItems))
		{
			return;
		}

		// Keep automatic description records matching our target (donwload) filename or URL
		$targetFilename = basename((($this->type == 'file') ? $this->filename : $this->url));

		$autoItems = array_filter($autoItems, function ($autoItem) use ($targetFilename) {
			if (empty($autoItem->packname))
			{
				return false;
			}

			return fnmatch($autoItem->packname, $targetFilename);
		});

		// If there are no items left bail out
		if (empty($autoItems))
		{
			return;
		}

		$auto = new AutodescriptionTable($this->getDbo());
		$auto->bind(array_shift($autoItems));

		// Apply environments
		$this->environments = $this->environments ?: $auto->environments;

		// Apply title
		$this->title = trim($this->title ?? '') ?: $auto->title;

		// Apply access
		$this->access = $this->access !== 1 || !$auto->access ? $this->access : $auto->access ;

		// Apply description, if necessary
		$stripDesc = trim(strip_tags($this->description));

		if (empty($this->description) || empty($stripDesc))
		{
			$this->description = $auto->description;
		}
	}

	/**
	 * Returns the applicable update stream ID for the current item
	 *
	 * @return  int|null  Update stream ID. NULL when no stream is applicable.
	 * @since   7.0.0
	 */
	protected function getUpdateStream(): ?int
	{
		$db = $this->getDBO();

		$subquery = $db->getQuery(true)
			->select($db->quoteName('category_id'))
			->from('#__ars_releases')
			->where($db->quoteName('id') . ' = ' . $db->quote($this->release_id));

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_updatestreams'))
			->where($db->quoteName('category') . ' IN (' . $subquery . ')');

		$streams = $db->setQuery($query)->loadObjectList() ?: [];

		if (empty($streams))
		{
			return null;
		}

		$targetFilename = basename((($this->type == 'file') ? $this->filename : $this->url));

		$streams = array_filter($streams, function ($stream) use ($targetFilename) {
			$pattern = $stream->packname;
			$element = $stream->element;

			if (empty($pattern) && !empty($element))
			{
				$pattern = $element . '*';
			}

			if (empty($pattern))
			{
				return false;
			}

			return fnmatch($pattern, $targetFilename);
		});

		if (empty($streams))
		{
			return null;
		}

		$stream = array_shift($streams);

		return $stream->id;
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
			$this->environments = @json_decode($this->environments) ?: [];
		}
	}

	protected function onBeforeBind($src, $ignore = [])
	{
		if (isset($src['environments']) && !is_array($src['environments']))
		{
			$src['environments'] = empty($src['environments']) ? [] : (@json_decode($src['environments']) ?: []);
		}
	}
}
