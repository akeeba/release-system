<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF40\Container\Container;
use FOF40\Model\DataModel;
use FOF40\Model\Mixin\Assertions;
use FOF40\Model\Mixin\ImplodedArrays;
use FOF40\Model\Mixin\JsonData;
use JDatabaseQuery;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

/**
 * Model for download items
 *
 * Fields:
 *
 * @property  int           $id
 * @property  int           $release_id
 * @property  string        $title
 * @property  string        $alias
 * @property  string        $description
 * @property  string        $type
 * @property  string        $filename
 * @property  string        $url
 * @property  int           $updatestream
 * @property  string        $md5
 * @property  string        $sha1
 * @property  string        $sha256
 * @property  string        $sha384
 * @property  string        $sha512
 * @property  int           $filesize
 * @property  int           $hits
 * @property  string        $created
 * @property  string        $modified
 * @property  int           $checked_out
 * @property  string        $checked_out_time
 * @property  int           $access
 * @property  bool          $show_unauth_links
 * @property  string        $redirect_unauth
 * @property  bool          $published
 * @property  string        $language
 * @property  array         $environments
 *
 * Filters:
 *
 * @method  $this  id()                 id(int $v)
 * @method  $this  item_id()            item_id(int $v)
 * @method  $this  release()            release(int $v)
 * @method  $this  release_id()         release_id(int $v)
 * @method  $this  title()              title(string $v)
 * @method  $this  alias()              alias(string $v)
 * @method  $this  description()        description(string $v)
 * @method  $this  type()               type(string $v)
 * @method  $this  filename()           filename(string $v)
 * @method  $this  url()                url(string $v)
 * @method  $this  updatestream()       updatestream(int $v)
 * @method  $this  md5()                md5(string $v)
 * @method  $this  sha1()               sha1(string $v)
 * @method  $this  filesize()           filesize(int $v)
 * @method  $this  hits()               hits(int $v)
 * @method  $this  created()            created(string $v)
 * @method  $this  created_by()         created_by(int $v)
 * @method  $this  modified()           modified(string $v)
 * @method  $this  modified_by()        modified_by(int $v)
 * @method  $this  checked_out()        checked_out(int $v)
 * @method  $this  checked_out_time()   checked_out_time(string $v)
 * @method  $this  ordering()           ordering(int $v)
 * @method  $this  access()             access(int $v)
 * @method  $this  show_unauth_links()  show_unauth_links(bool $v)
 * @method  $this  redirect_unauth()    redirect_unauth(string $v)
 * @method  $this  published()          published(bool $v)
 * @method  $this  environments()       environments(string $v)
 * @method  $this  category()           category(int $v)
 * @method  $this  language()           language(string $v)
 * @method  $this  language2()          language2(string $v)
 * @method  $this  access_user()        access_user(int $user_id)
 * @method  $this  orderby_filter()     orderby_filter(string $orderMethod)
 *
 * Relations:
 *
 * @property  Releases      $release
 * @property  UpdateStreams $updateStreamObject
 *
 **/
class Items extends DataModel
{
	use ImplodedArrays;
	use Assertions;
	use JsonData;
	use Mixin\VersionedCopy
	{
		Mixin\VersionedCopy::onBeforeCopy as onBeforeCopyVersioned;
	}
	use Mixin\ClearCacheAfterActions;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF40\Model\DataModel\Exception\NoTableColumns
	 * @see DataModel::__construct()
	 *
	 */
	public function __construct(Container $container, array $config = [])
	{
		$config['tableName']   = '#__ars_items';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'slug'        => 'alias',
			'enabled'     => 'published',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		];

		// Automatic checks should not take place on these fields:
		$config['fieldsSkipChecks'] = [
			'description',
			'filename',
			'url',
			'updatestream',
			'md5',
			'sha1',
			'filesize',
			'hits',
			'created',
			'created_by',
			'modified',
			'modified_by',
			'checked_out',
			'checked_out_time',
			'ordering',
			'show_unauth_links',
			'redirect_unauth',
			'language',
			'environments',
		];

		parent::__construct($container, $config);

		// Relations
		$this->belongsTo('release', 'Releases', 'release_id', 'id');
		$this->hasOne('updateStreamObject', 'UpdateStreams', 'updatestream', 'id');

		$this->with(['release', 'updateStreamObject']);

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('RelationFilters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');

		// Some filters we will have to handle programmatically so we need to exclude them from the behaviour
		$this->blacklistFilters([
			//'release_id', // I actually need this for eager loading...
			'language',
		]);

		// Defaults
		$this->access = 1;
	}

	/**
	 * Change the ordering of the records of the table
	 *
	 * @param   string  $where  The WHERE clause of the SQL used to fetch the order
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function reorder($where = '')
	{
		if (empty($where))
		{
			$where = $this->getReorderWhere();
		}

		return parent::reorder($where);
	}

	public function check(): self
	{
		$this->assertNotEmpty($this->release_id, 'ERR_ITEM_NEEDS_CATEGORY');

		// Get some useful info
		$db    = $this->getDBO();
		$query = $db->getQuery(true)
			->select([
				$db->qn('title'),
				$db->qn('alias'),
			])->from($db->qn('#__ars_items'))
			->where($db->qn('release_id') . ' = ' . $db->q($this->release_id));

		if ($this->id)
		{
			$query->where('NOT(' . $db->qn('id') . '=' . $db->q($this->id) . ')');
		}

		$db->setQuery($query);
		$info    = $db->loadAssocList();
		$titles  = [];
		$aliases = [];

		foreach ($info as $infoitem)
		{
			$titles[]  = $infoitem['title'];
			$aliases[] = $infoitem['alias'];
		}

		// Let's get automatic item title/description records
		$subQuery = $db->getQuery(true)
			->select($db->qn('category_id'))
			->from($db->qn('#__ars_releases'))
			->where($db->qn('id') . ' = ' . $db->q($this->release_id));

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ars_autoitemdesc'))
			->where($db->qn('category') . ' IN (' . $subQuery . ')')
			->where('NOT(' . $db->qn('published') . '=' . $db->q(0) . ')')
			->order($db->qn('id') . ' ASC');

		$db->setQuery($query);

		$autoitems = $db->loadObjectList();
		$auto      = (object) ['title' => '', 'description' => '', 'environments' => ''];

		if (!empty($autoitems))
		{
			$fname = basename((($this->type == 'file') ? $this->filename : $this->url));

			foreach ($autoitems as $autoitem)
			{
				$pattern = $autoitem->packname;

				if (empty($pattern))
				{
					continue;
				}

				if (fnmatch($pattern, $fname))
				{
					$auto = $autoitem;
					break;
				}
			}
		}

		// Added environment ID
		if (empty($this->environments) && !empty($auto->environments))
		{
			$this->environments = explode(',', $auto->environments);
		}

		if (!empty($this->environments) && is_array($this->environments))
		{
			// Filter out empty environments
			$temp = [];

			foreach ($this->environments as $eid)
			{
				if ($eid)
				{
					$temp[] = $eid;
				}
			}

			$this->environments = $temp;
		}

		// Check if a title exists
		if (!$this->title)
		{
			// No, try the automatic rule-based title
			$this->title = $auto->title;

			if (!$this->title)
			{
				// No, try to get the filename
				switch ($this->type)
				{
					case 'file':
						if ($this->filename)
						{
							$this->title = basename($this->filename);
						}

						break;

					case 'link':
						if ($this->url)
						{
							$this->title = basename($this->url);
						}

						break;
				}

				$this->assertNotEmpty($this->title, 'ERR_ITEM_NEEDS_TITLE');
			}
		}

		$this->assertNotInArray($this->title, $titles, 'ERR_ITEM_NEEDS_TITLE_UNIQUE');

		$stripDesc = strip_tags($this->description);
		$stripDesc = trim($stripDesc);

		if (empty($this->description) || empty($stripDesc))
		{
			$this->description = $auto->description;
		}

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$source = $this->title;

			switch ($this->type)
			{
				case 'file':
					if ($this->filename)
					{
						$source = basename($this->filename);
					}

					break;

				case 'link':
					if ($this->url)
					{
						$source = basename($this->url);
					}

					break;
			}

			$this->alias = str_replace('.', '-', $source);

			// Create a smart alias
			$alias = strtolower($source);
			$alias = str_replace(' ', '-', $alias);
			$alias = str_replace('.', '-', $alias);

			$this->alias = (string) preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		$this->assertNotEmpty($this->alias, 'ERR_ITEM_NEEDS_ALIAS');

		$this->assertNotInArray($this->alias, $aliases, 'ERR_ITEM_NEEDS_ALIAS_UNIQUE');

		$this->assertInArray($this->type, ['link', 'file'], 'ERR_ITEM_NEEDS_TYPE');

		switch ($this->type)
		{
			case 'file':
				$this->assertNotEmpty($this->filename, 'ERR_ITEM_NEEDS_FILENAME');
				break;

			case 'link':
				$this->assertNotEmpty($this->url, 'ERR_ITEM_NEEDS_LINK');
				break;
		}

		$filter = InputFilter::getInstance([], [], 1, 1);

		// Filter the description using a safe HTML filter
		if (!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Set a default access
		if ($this->access <= 0)
		{
			$this->access = 1;
		}

		if (is_null($this->published) || ($this->published == ''))
		{
			$this->published = 0;
		}

		// Apply an update stream, if possible
		if (empty($this->updatestream))
		{
			$db = $this->getDBO();

			$subquery = $db->getQuery(true)
				->select($db->qn('category_id'))
				->from('#__ars_releases')
				->where($db->qn('id') . ' = ' . $db->q($this->release_id));

			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ars_updatestreams'))
				->where($db->qn('category') . ' IN (' . $subquery . ')');

			$db->setQuery($query);
			$streams = $db->loadObjectList();

			if (!empty($streams))
			{
				$fname = basename((($this->type == 'file') ? $this->filename : $this->url));

				foreach ($streams as $stream)
				{
					$pattern = $stream->packname;
					$element = $stream->element;

					if (empty($pattern) && !empty($element))
					{
						$pattern = $element . '*';
					}

					if (empty($pattern))
					{
						continue;
					}

					if (fnmatch($pattern, $fname))
					{
						$this->updatestream = $stream->id;

						break;
					}
				}
			}
		}

		// Check for MD5 and SHA1 existence
		if (empty($this->md5) || empty($this->sha1) || empty($this->sha256) || empty($this->sha384) || empty($this->sha512) || empty($this->filesize))
		{
			if ($this->type == 'file')
			{
				$target   = null;
				$folder   = null;
				$filename = $this->filename;

				/** @var Releases $releaseModel */
				$releaseModel = $this->container->factory->model('Releases')->tmpInstance();
				/** @var Releases $release */
				$release = $releaseModel->find($this->release_id);

				if ($release->id)
				{
					if ($release->category->id)
					{
						$folder = $release->category->directory;
					}
				}

				$url = null;

				if (!empty($folder))
				{
					if (!Folder::exists($folder))
					{
						$folder = JPATH_ROOT . '/' . $folder;

						if (!Folder::exists($folder))
						{
							$folder = null;
						}
					}

					if (!empty($folder))
					{
						$filename = $folder . '/' . $filename;
					}
				}
			}

			if (!isset($url))
			{
				$url = null;
			}

			if (($this->type == 'link') || !is_null($url))
			{
				if (is_null($url))
				{
					$url = $this->url;
				}

				$config = $this->container->platform->getConfig();

				$target = $config->get('tmp_path') . '/temp.dat';

				if (function_exists('curl_exec'))
				{
					// By default, try using cURL
					$process = curl_init($url);
					curl_setopt($process, CURLOPT_HEADER, 0);
					// Pretend we are IE7, so that webservers play nice with us
					curl_setopt($process, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');
					curl_setopt($process, CURLOPT_TIMEOUT, 5);
					curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
					// The @ sign allows the next line to fail if open_basedir is set or if safe mode is enabled
					@curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
					@curl_setopt($process, CURLOPT_MAXREDIRS, 20);
					$data = curl_exec($process);

					if ($data !== false)
					{
						$result = File::write($target, $data);
					}

					curl_close($process);
				}
				else
				{
					// Use Joomla!'s download helper
					InstallerHelper::downloadPackage($url, $target);
				}

				$filename = $target;
			}

			if (!empty($filename) && is_file($filename))
			{
				if (!File::exists($filename))
				{
					$filename = null;
				}
			}

			if (!empty($filename) && is_file($filename))
			{
				if (function_exists('hash_file'))
				{
					if (empty($this->md5))
					{
						$this->md5 = hash_file('md5', $filename);
					}

					if (empty($this->sha1))
					{
						$this->sha1 = hash_file('sha1', $filename);
					}

					if (empty($this->sha256))
					{
						$this->sha256 = hash_file('sha256', $filename);
					}

					if (empty($this->sha384))
					{
						$this->sha384 = hash_file('sha384', $filename);
					}

					if (empty($this->sha512))
					{
						$this->sha512 = hash_file('sha512', $filename);
					}
				}
				else
				{
					if (function_exists('md5_file') && empty($this->md5))
					{
						$this->md5 = md5_file($filename);
					}

					if (function_exists('sha1_file') && empty($this->sha1))
					{
						$this->sha1 = sha1_file($filename);
					}

					// NOTE: You're crap outta luck for better checksums in this case, sorry
				}

				if (empty($this->filesize))
				{
					$filesize = @filesize($filename);

					if ($filesize !== false)
					{
						$this->filesize = $filesize;
					}
				}
			}

			if (!empty($filename) && is_file($filename) && ($this->type == 'link'))
			{
				if (!@unlink($filename))
				{
					File::delete($filename);
				}
			}
		}

		return parent::check();
	}

	/**
	 * Returns a list of select options which will let the user pick a file for a release.
	 *
	 * Files already used in other items of the same category will not be listed to prevent the list getting too long.
	 *
	 * @param   int  $release_id  The numeric ID of the release selected by the user
	 * @param   int  $item_id     The numeric ID of the current item. Leave 0 if it's a new item.
	 *
	 * @return  array  Array of JHtml options.
	 * @see     Releases::directoryForRelease()
	 */
	public function getFilesOptions(int $release_id, int $item_id = 0): array
	{
		// Default options –– basically, an empty select
		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', '- ' . Text::_('LBL_ITEMS_FILENAME_SELECT') . ' -');

		// Get the directory where this category holds its files
		/** @var Releases $releaseModel */
		$releaseModel = $this->container->factory->model('Releases')->tmpInstance();
		$directory    = $releaseModel->directoryForRelease($release_id);

		if (empty($directory))
		{
			return $options;
		}

		$directory .= in_array(substr($directory, -1), ['/', DIRECTORY_SEPARATOR]) ? '' : '/';

		// Get all files under this directory and remove the directory prefix
		$allFiles = Folder::files($directory, '.', true, true);
		$allFiles = array_map(function ($thisFolder) use ($directory) {
			$dirLen = strlen($directory);
			if (substr($thisFolder, 0, $dirLen) == $directory)
			{
				$thisFolder = substr($thisFolder, $dirLen);
			}

			return ltrim($thisFolder, '/' . DIRECTORY_SEPARATOR);
		}, $allFiles);

		// Get a list of files already used in this category (so as not to show them again, he he!)
		$files           = [];
		$itemsModel      = $this->tmpInstance();
		$release         = $releaseModel->find($release_id);
		$items           = $itemsModel
			->category($release->category_id)
			->release('false')
			->get(true);
		$currentFilename = '';

		$items->each(function ($item) use (&$files, &$currentFilename, $item_id) {
			if (empty($item->filename))
			{
				return;
			}

			$files[$item->id] = $item->filename;
		});

		if (isset($files[$item_id]))
		{
			$currentFilename = $files[$item_id];

			unset($files[$item_id]);
		}

		// Produce a list of files and remove the items in the $files array
		$files    = array_unique($files);
		$useFiles = array_diff($allFiles, $files);

		if (empty($useFiles))
		{
			return $options;
		}

		foreach ($useFiles as $file)
		{
			$options[] = HTMLHelper::_('select.option', $file, $file);
		}

		return $options;
	}

	/**
	 * Implements custom filtering
	 *
	 * @param   JDatabaseQuery  $query           The model query we're operating on
	 * @param   bool            $overrideLimits  Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(JDatabaseQuery &$query, bool $overrideLimits = false): void
	{
		$db = $this->getDbo();

		$fltCategory = $this->getState('category', null, 'int');

		if ($fltCategory > 0)
		{
			// Filter the categories table, too
			$this->whereHas('release', function (JDatabaseQuery $subQuery) use ($db, $fltCategory) {
				$subQuery->where($db->qn('category_id') . ' = ' . $db->q($fltCategory));
			});
		}

		$fltItemId = $this->getState('item_id', null, 'int');

		if ($fltItemId > 0)
		{
			$query->where($db->qn('id') . ' = ' . $db->q($fltItemId));
		}

		$fltRelease = $this->getState('release_id', null, 'array');

		if (!is_array($fltRelease))
		{
			$fltRelease = $this->getState('release_id', null, 'int');
		}
		else
		{
			$fltRelease = null;
		}

		$fltRelease = $this->getState('release', $fltRelease, 'int');

		if ($fltRelease > 0)
		{
			$query->where($db->qn('release_id') . ' = ' . $db->q($fltRelease));
		}

		$fltAccessUser = $this->getState('access_user', null, 'int');

		if (!is_null($fltAccessUser))
		{
			$user = $this->container->platform->getUser($fltAccessUser);

			if (!is_object($user) || !($user instanceof User))
			{
				$access_levels = [-1];
			}
			else
			{
				$access_levels = $this->container->platform->getUser($fltAccessUser)->getAuthorisedViewLevels();

				if (empty($access_levels))
				{
					// Essentially, tell it to find nothing if no our user is authorised to no access levels
					$access_levels = [-1];
				}
			}

			$access_levels = array_unique($access_levels);

			// Filter this table
			$access_levels_escaped = array_map([$db, 'quote'], $access_levels);
			$query->where(
				'(' .
				'(' . $db->qn('access') . ' IN (' . implode(',', $access_levels_escaped) . ')) OR (' .
				$db->qn('show_unauth_links') . ' = ' . $db->q(1)
				. '))'
			);

			/** @var Categories $categoriesModel */
			$categories      = [];
			$categoriesModel = $this->container->factory->model('Categories')->tmpInstance();
			$categoriesModel->access($access_levels)->get(true)->transform(function ($item) use (&$categories) {
				$categories[] = $item->id;
			});

			if (empty($categories))
			{
				// Essentially, tell it to find nothing if no categories fulfill our criteria
				$categories = [-1];
			}

			// We have a category filter.
			if (!empty($fltCategory))
			{
				// If the category exists in our list of allowed categories, filter by it
				if (in_array($fltCategory, $categories))
				{
					$categories = [$fltCategory];
				}
				// Otherwise show nothing (selected category is unreachable)
				else
				{
					$categories = [-1];
				}
			}

			$categories = array_map([$db, 'quote'], $categories);

			$this->whereHas('release', function (JDatabaseQuery $subQuery) use ($db, $access_levels, $categories) {
				$subQuery->where($db->qn('category_id') . ' IN (' . implode(',', $categories) . ')');
				$subQuery->where($db->qn('access') . ' IN (' . implode(',', $access_levels) . ')');
			});
		}

		$fltLanguage  = $this->getState('language', null, 'cmd');
		$fltLanguage2 = $this->getState('language2', null, 'string');

		if (($fltLanguage != '*') && ($fltLanguage != ''))
		{
			$query->where($db->qn('language') . ' IN (' . $db->q('*') . ',' . $db->q($fltLanguage) . ')');

			/** @var Categories $categoriesModel */
			$categories      = [];
			$categoriesModel = $this->container->factory->model('Categories')->tmpInstance();
			$categoriesModel->language($fltLanguage)->get(true)->transform(function ($item) use (&$categories) {
				/** @var Categories $item */
				$categories[] = $item->id;
			});

			if (empty($categories))
			{
				// Essentially, tell it to find nothing if no categories fulfill our criteria
				$categories = [-1];
			}

			if (!empty($fltCategory))
			{
				// If the category exists in our list of allowed categories, filter by it
				if (in_array($fltCategory, $categories))
				{
					$categories = [$fltCategory];
				}
				// Otherwise show nothing (selected category is unreachable)
				else
				{
					$categories = [-1];
				}
			}

			$this->whereHas('release', function (JDatabaseQuery $subQuery) use ($db, $fltLanguage, $categories) {
				$categories = array_map([$db, 'quote'], $categories);

				$subQuery->where($db->qn('language') . ' IN (' . $db->q('*') . ',' . $db->q($fltLanguage) . ')');
				$subQuery->where($db->qn('category_id') . ' IN (' . implode(',', $categories) . ')');
			});
		}
		elseif ($fltLanguage2)
		{
			$query->where($db->qn('language') . ' = ' . $db->q($fltLanguage2));

			/** @var Categories $categoriesModel */
			$categories      = [];
			$categoriesModel = $this->container->factory->model('Categories')->tmpInstance();
			$categoriesModel->language2($fltLanguage2)->get(true)->transform(function ($item) use (&$categories) {
				$categories[] = $item->id;
			});

			if (empty($categories))
			{
				// Essentially, tell it to find nothing if no categories fulfill our criteria
				$categories = [-1];
			}

			if (!empty($fltCategory))
			{
				// If the category exists in our list of allowed categories, filter by it
				if (in_array($fltCategory, $categories))
				{
					$categories = [$fltCategory];
				}
				// Otherwise show nothing (selected category is unreachable)
				else
				{
					$categories = [-1];
				}
			}

			$this->whereHas('release', function (JDatabaseQuery $subQuery) use ($db, $fltLanguage2, $categories) {
				$categories = array_map([$db, 'quote'], $categories);

				$subQuery->where($db->qn('language') . ' = ' . $db->q($fltLanguage2));
				$subQuery->where($db->qn('category_id') . ' IN (' . implode(',', $categories) . ')');
			});
		}

		// Default ordering ID descending (latest created release on top)
		$filterOrder    = $this->getState('filter_order', 'id');
		$filterOrderDir = $this->getState('filter_order_Dir', 'DESC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);

		// Order filtering
		$fltOrderBy = $this->getState('orderby_filter', null, 'cmd');

		switch ($fltOrderBy)
		{
			case 'alpha':
				$this->setState('filter_order', 'title');
				$this->setState('filter_order_Dir', 'ASC');
				break;

			case 'ralpha':
				$this->setState('filter_order', 'title');
				$this->setState('filter_order_Dir', 'DESC');
				break;

			case 'created':
				$this->setState('filter_order', 'created');
				$this->setState('filter_order_Dir', 'ASC');
				break;

			case 'rcreated':
				$this->setState('filter_order', 'created');
				$this->setState('filter_order_Dir', 'DESC');
				break;

			case 'order':
				$this->setState('filter_order', 'ordering');
				$this->setState('filter_order_Dir', 'ASC');
				break;
		}
	}

	/**
	 * Runs before copying an Item
	 *
	 * @return  void
	 * @see  Releases::onBeforeCopy  for the concept
	 *
	 */
	protected function onBeforeCopy(): void
	{
		$this->onBeforeCopyVersioned();

		$this->enabled = false;
	}

	/**
	 * Reorders the entire releases array on inserting a new object and sets the current ordering to 1
	 *
	 * @param   \stdClass  $dataObject
	 *
	 * @return  void
	 */
	protected function onBeforeCreate(object &$dataObject): void
	{
		$dataObject->ordering = 1;
		$this->ordering       = 1;

		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->update($db->qn('#__ars_items'))
			->set($db->qn('ordering') . ' = ' . $db->qn('ordering') . ' + ' . $db->q(1));

		// Only update items with the same release (as long as a release is – and it should be!) defined
		if (isset($dataObject->release_id) && !empty($dataObject->release_id))
		{
			$query->where($db->qn('release_id') . ' = ' . $db->q($dataObject->release_id));
		}

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			// Do not fail on database error
		}
	}

	/**
	 * Converts the loaded JSON-encoded list of environments into an array
	 *
	 * @param   string|array  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getEnvironmentsAttribute($value): array
	{
		$environments = $this->getAttributeForJson($value);

		if (!is_array($environments))
		{
			$environments = [];
		}

		return $environments;
	}

	/**
	 * Converts the array of environments into a JSON-encoded string
	 *
	 * @param   array|string  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setEnvironmentsAttribute($value): string
	{
		return $this->setAttributeForJson($value);
	}

	/**
	 * Get the default WHERE clause for reordering items. Called by reorder().
	 *
	 * @return  string
	 */
	private function getReorderWhere(): string
	{
		$where        = [];
		$fltCategory  = $this->getState('category', null, 'int');
		$fltRelease   = $this->getState('release', null, 'int');
		$fltPublished = $this->getState('published', null, 'cmd');

		$db = $this->getDBO();

		if ($fltCategory)
		{
			/** @var Releases $releasesModel */
			$releases      = [];
			$releasesModel = $this->container->factory->model('Releases')->tmpInstance();
			$releasesModel->category($fltCategory)->get(true)->transform(function ($item) use (&$releases) {
				$releases[] = $item->id;
			});

			if (!empty($releases))
			{
				$releases = array_map([$db, 'quote'], $releases);
				$where[]  = $db->qn('release_id') . ' IN (' . implode(',', $releases) . ')';
			}
		}

		if ($fltRelease)
		{
			$where[] = $db->qn('release_id') . ' = ' . $db->q($fltRelease);
		}

		if ($fltPublished != '')
		{
			$where[] = $db->qn('published') . ' = ' . $db->q($fltPublished);
		}

		if (count($where))
		{
			return '(' . implode(') AND (', $where) . ')';
		}
		else
		{
			return '';
		}
	}
}
