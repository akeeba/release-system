<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for Releases
 *
 * Fields:
 *
 * @property  int     $id
 * @property  int     $category_id
 * @property  string  $version
 * @property  string  $alias
 * @property  string  $maturity
 * @property  string  $description
 * @property  string  $notes
 * @property  array   $groups
 * @property  int     $hits
 * @property  string  $created
 * @property  string  $modified
 * @property  int     $checked_out
 * @property  string  $checked_out_time
 * @property  int     $access
 * @property  bool    $show_unauth_links
 * @property  string  $redirect_unauth
 * @property  bool    $published
 * @property  string  $language
 *
 * Filters:
 *
 * @method  $this  id()                 id(int $v)
 * @method  $this  category()           category(int $v)
 * @method  $this  category_id()        category_id(int $v)
 * @method  $this  version()            version(string $v)
 * @method  $this  alias()              alias(string $v)
 * @method  $this  maturity()           maturity(string $v)
 * @method  $this  description()        description(string $v)
 * @method  $this  notes()              notes(string $v)
 * @method  $this  groups()             groups(string $v)
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
 * @method  $this  language()           language(string $v)
 * @method  $this  language2()          language2(string $v)
 * @method  $this  access_user()        access_user(int $user_id)
 * @method  $this  nobeunpub()          nobeunpub(bool $v)
 * @method  $this  latest()             latest(bool $v)
 * @method  $this  orderby_filter()     orderby_filter(string $orderMethod)
 *
 * Relations:
 *
 * @property  Categories  $category
 * @property  Items       $items
 *
 **/
class Releases extends DataModel
{
	use Mixin\ImplodedArrays;
	use Mixin\Assertions;
	use Mixin\VersionedCopy {
		Mixin\VersionedCopy::onBeforeCopy as onBeforeCopyVersioned;
	}

	/** @var  DataModel\Collection  Used to handle copies */
	protected static $itemsBeforeCopy = null;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @see DataModel::__construct()
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF30\Model\DataModel\Exception\NoTableColumns
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__ars_releases';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'title'       => 'version',
			'slug' 	      => 'alias',
			'enabled'     => 'published',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		];

		// Automatic checks should not take place on these fields:
		$config['fieldsSkipChecks'] = [
			'description',
			'notes',
			'groups',
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
		];

		parent::__construct($container, $config);

		// Relations
		$this->belongsTo('category', 'Categories', 'category_id', 'id');
		$this->hasMany('items', 'Items', 'id', 'release_id');

		$this->with(['category']);

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('RelationFilters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');

		// Some filters we will have to handle programmatically so we need to exclude them from the behaviour
		$this->blacklistFilters([
			//'category_id', // I actually need this for eager loading...
			'language',
			'maturity'
		]);

		// Defaults
		$this->access = 1;
		$this->maturity = 'alpha';
		$this->language = '*';
	}

	/**
	 * Implements custom filtering
	 *
	 * @param   \JDatabaseQuery  $query           The model query we're operating on
	 * @param   bool             $overrideLimits  Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$db = $this->getDbo();

		$logCategoryLimit = $this->getState('logCategoryLimit', 0, 'int');
		$fltCategory = null;

		/**
		 * In the back-end of the component when the model state 'logCategoryLimit' is set to 1 we will get the default
		 * category ID to filter by from the 'category' state variable of the Logs model. This allows us to do a
		 * drill-down search in the logs using nothing but XML forms.
		 */
		if ($this->container->platform->isBackend() && $logCategoryLimit)
		{
			/** @var Categories $logsModel */
			$logsModel = $this->container->factory->model('Logs');
			$fltCategory = $logsModel->getState('category', null, 'int');
		}

		$fltCategory = $this->getState('category_id', $fltCategory, 'raw');

		if (is_array($fltCategory))
		{
			if (isset($fltCategory['method']) && ($fltCategory['method'] == 'exact') && isset($fltCategory['value']))
			{
				$fltCategory = (int) $fltCategory['value'];
			}
			else
			{
				$fltCategory = 0;
			}
		}
		else
		{
			$fltCategory = $this->getState('category_id', $fltCategory, 'int');
		}

		$fltCategory = $this->getState('category', $fltCategory, 'int');

		if ($fltCategory > 0)
		{
			$query->where($db->qn('category_id') . ' = ' . $db->q($fltCategory));
		}

		$fltAccessUser = $this->getState('access_user', null, 'int');

		if (!is_null($fltAccessUser))
		{
			$access_levels = $this->container->platform->getUser($fltAccessUser)->getAuthorisedViewLevels();

			if (empty($access_levels))
			{
				// Essentially, tell it to find nothing if no our user is authorised to no access levels
				$access_levels = [$db->q(-1)];
			}

			$access_levels = array_map(array($db, 'quote'), $access_levels);

			// Filter this table
			$query->where(
				'(' .
				'('. $db->qn('access') . ' IN (' . implode(',', $access_levels) . ')) OR (' .
				$db->qn('show_unauth_links') . ' = ' . $db->q(1)
				. '))'
			);

			// Filter the categories table, too
			$this->whereHas('category', function(\JDatabaseQuery $subQuery) use($access_levels, $db) {
				$subQuery->where($db->qn('access') . ' IN (' . implode(',', $access_levels) . ')');
			});
		}

		$fltNoBEUnpub = $this->getState('nobeunpub', null, 'int');

		if ($fltNoBEUnpub)
		{
			$published = $this->getState('published', '');

			if ($published != '')
			{
				// Filter this table
				$query->where($db->qn('published') . ' = ' . $db->q($published));
			}

			// Filter the categories table, too
			$this->whereHas('category', function(\JDatabaseQuery $subQuery) use($db) {
				$subQuery->where('NOT(' . $db->qn('published') . ' = ' . $db->q('0') . ' AND ' .
					$db->qn('type') . '=' . $db->q('bleedingedge') . ')');
			});
		}

		$fltLanguage = $this->getState('language', null, 'cmd');
		$fltLanguage2 = $this->getState('language2', null, 'string');

		if (($fltLanguage != '*') && ($fltLanguage != ''))
		{
			// Filter this table
			$query->where($db->qn('language') . ' IN (' . $db->q('*') . ',' . $db->q($fltLanguage) . ')');

			// Filter the categories table, too
			$this->whereHas('category', function(\JDatabaseQuery $subQuery) use($db, $fltLanguage) {
				$subQuery->where($db->qn('language') . ' IN (' . $db->q('*') . ',' . $db->q($fltLanguage) . ')');
			});
		}
		elseif ($fltLanguage2)
		{
			// Filter this table
			$query->where($db->qn('language') . ' = ' . $db->q($fltLanguage2));

			// Filter the categories table, too
			$this->whereHas('category', function(\JDatabaseQuery $subQuery) use($db, $fltLanguage2) {
				$subQuery->where($db->qn('language') . ' = ' . $db->q($fltLanguage2));
			});
		}

		$fltMaturity = $this->getState('maturity', 'alpha', 'cmd');

		switch ($fltMaturity)
		{
			case 'beta':
				$query->where($db->qn('maturity') . ' IN (' . $db->q('beta'), ',' . $db->q('rc') . ',' . $db->q('stable') . ')');
				break;
			case 'rc':
				$query->where($db->qn('maturity') . ' IN (' . $db->q('rc') . ',' . $db->q('stable') . ')');
				break;
			case 'stable':
				$query->where($db->qn('maturity') . ' = ' . $db->q('stable'));
				break;
		}

		// Default ordering ID descending (latest created release on top)
		$filterOrder = $this->getState('filter_order', 'id');
		$filterOrderDir = $this->getState('filter_order_Dir', 'DESC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);

		// Order filtering
		$fltOrderBy = $this->getState('orderby_filter', null, 'cmd');

		// Latest version filter. Use as $releases->published(1)->latest(true)->get(true)
		$fltLatest = $this->getState('latest', false, 'bool');

		if ($fltLatest)
		{
			$fltOrderBy = 'order';
		}

		switch ($fltOrderBy)
		{
			case 'alpha':
				$this->setState('filter_order', 'version');
				$this->setState('filter_order_Dir', 'ASC');
				break;

			case 'ralpha':
				$this->setState('filter_order', 'version');
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
	 * Implements custom filtering
	 *
	 * @param   \JDatabaseQuery  $query           The model query we're operating on
	 * @param   bool             $overrideLimits  Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onAfterBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$db = $this->getDbo();

		// Latest version filter. Use as $releases->published(1)->latest(true)->get(true)
		$fltLatest = $this->getState('latest', false, 'bool');

			if ($fltLatest)
		{
			$subQuery = $db->getQuery(true)
				->select($db->qn('r1.id'))
				->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r1'))
				->leftJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r2') . ' ON ('.
					$db->qn('r1.category_id') . ' = ' . $db->qn('r2.category_id') . ' AND ' .
					$db->qn('r1.ordering') . ' > ' . $db->qn('r2.ordering')
					.')')
				->where($db->qn('r2.ordering') . ' IS NULL');

			$query->where($db->qn('id') . ' IN(' . $subQuery . ')');
		}

		//echo $query;die;
	}



	/**
	 * Change the ordering of the records of the table
	 *
	 * @param   string $where The WHERE clause of the SQL used to fetch the order
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

	/**
	 * Helper function to force eager loading of the whole set. In this way we can perform lookup on fields without the
	 * need to setup the whole relationship on the model
	 *
	 * @param $id
	 * @param $field
	 *
	 * @return null|string
	 */
	public static function forceEagerLoad($id, $field)
	{
		static $cache;

		if (!$cache)
		{
			$container = Container::getInstance('com_ars');
			$db = $container->db;

			$query = $db->getQuery(true)
						->select('*')
						->from($db->qn('#__ars_releases'));
			$cache = $db->setQuery($query)->loadObjectList('id');
		}

		if (!isset($cache[$id]) || !isset($cache[$id]->$field))
		{
			return null;
		}

		return $cache[$id]->$field;
	}

	/**
	 * Get the default WHERE clause for reordering items. Called by reorder().
	 *
	 * @return string
	 */
	private function getReorderWhere()
	{
		$where = array();

		$fltCategory = $this->getState('category', null, 'int');
		$fltPublished = $this->getState('published', null, 'cmd');

		$db = $this->getDBO();

		if ($fltCategory)
		{
			$where[] = $db->qn('category_id') . ' = ' . $db->q($fltCategory);
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

	public function check()
	{
		$this->assertNotEmpty($this->category_id, 'COM_RELEASE_ERR_NEEDS_CATEGORY');

		// Get some useful info
		$db = $this->getDBO();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('version'),
				$db->qn('alias')
			))->from($db->qn('#__ars_releases'))
			->where($db->qn('category_id') . ' = ' . $db->q($this->category_id));

		if ($this->id)
		{
			$query->where('NOT(' . $db->qn('id') . '=' . $db->q($this->id) . ')');
		}

		$db->setQuery($query);
		$info = $db->loadAssocList();
		$versions = array();
		$aliases = array();

		foreach ($info as $infoitem)
		{
			$versions[] = $infoitem['version'];
			$aliases[] = $infoitem['alias'];
		}

		$this->assertNotEmpty($this->version, 'COM_RELEASE_ERR_NEEDS_VERSION');

		$this->assertNotInArray($this->version, $versions, 'COM_RELEASE_ERR_NEEDS_VERSION_UNIQUE');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			\JLoader::import('joomla.filter.input');

			// Get the category title
			/** @var Categories $catModel */
			$catModel = $this->container->factory->model('Categories')->tmpInstance();
			$catItem = $catModel->find($this->category_id);

			// Create a smart alias
			$alias = strtolower($catItem->alias . '-' . $this->version);
			$alias = str_replace(' ', '-', $alias);
			$alias = str_replace('.', '-', $alias);
			$this->alias = (string)preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		$this->assertNotEmpty($this->alias, 'COM_RELEASE_ERR_NEEDS_ALIAS');

		$this->assertNotInArray($this->alias, $aliases, 'COM_RELEASE_ERR_NEEDS_ALIAS_UNIQUE');

		// Automaticaly fix the maturity
		if (!in_array($this->maturity, array('alpha', 'beta', 'rc', 'stable')))
		{
			$this->maturity = 'beta';
		}

		\JLoader::import('joomla.filter.filterinput');
		$filter = \JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if (!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Filter the notes using a safe HTML filter
		if (!empty($this->notes))
		{
			$this->notes = $filter->clean($this->notes);
		}

		// Set the access to registered if there are subscriptions defined
		if (!empty($this->groups) && ($this->access == 1))
		{
			$this->access = 2;
		}

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}

		return parent::check();
	}

	/**
	 * Reorders the entire releases array on inserting a new object and sets the current ordering to 1
	 *
	 * @param   \stdClass  $dataObject
	 *
	 * @return  void
	 */
	protected function onBeforeCreate(&$dataObject)
	{
		$dataObject->ordering = 1;
		$this->ordering = 1;

		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->update($db->qn('#__ars_releases'))
			->set($db->qn('ordering') . ' = ' . $db->qn('ordering') . ' + ' . $db->q(1));

		// Only update releases with the same category (as long as a category is – and it should be!) defined
		if (isset($dataObject->category_id) && !empty($dataObject->category_id))
		{
			$query->where($db->qn('category_id') . ' = ' . $db->q($dataObject->category_id));
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
	 * Checks if we are allowed to delete this record. If there are items linked to this release then the deletion
	 * will fails with a RuntimeException.
	 *
	 * @param   int  $oid  The numeric ID of the category to delete
	 *
	 * @return  void
	 */
	function onBeforeDelete(&$oid)
	{
		$joins = array(
			array(
				'label'     => 'item',
				'name'      => '#__ars_items',
				'idfield'   => 'id',
				'idalias'   => 'item_id',
				'joinfield' => 'release_id'
			)
		);

		$this->canDelete($oid, $joins);
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getGroupsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setGroupsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}

	/**
	 * Runs before copying a Release
	 *
	 * @see  Categories::onBeforeCopy  for the concept
	 *
	 * @return  void
	 */
	protected function onBeforeCopy()
	{
		self::$itemsBeforeCopy = clone $this->items;

		$this->onBeforeCopyVersioned();

		$this->enabled = false;
	}

	/**
	 * Runs after copying a Release
	 *
	 * @see  Categories::onAfterCopy  for the concept
	 *
	 * @return  void
	 */
	protected function onAfterCopy(Releases &$releaseAfterCopy)
	{
		if (!is_object(self::$itemsBeforeCopy) || !(self::$itemsBeforeCopy instanceof DataModel\Collection))
		{
			return;
		}

		self::$itemsBeforeCopy->map(function($item) use($releaseAfterCopy) {
			/** @var  Items  $item */
			$item->copy([
				'release_id' => $releaseAfterCopy->id
			]);
		});

		self::$itemsBeforeCopy = null;
	}
}
