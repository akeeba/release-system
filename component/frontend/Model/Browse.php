<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use FOF30\Model\DataModel\Collection;
use FOF30\Model\Model;

/**
 * Class Browse
 *
 * @package Akeeba\ReleaseSystem\Site\Model
 *
 * @method  $this  grouping() grouping(string $v)
 * @method  $this  orderby() orderby(string $v)
 * @method  $this  rel_orderby() rel_orderby(string $v)
 * @method  $this  limit() limit(int $limit)
 * @method  $this  limitstart() limitstart(int $limitStart)
 * @method  $this  items_orderby() items_orderby(int $limitStart)
 * @method  $this  maturity() maturity(int $limitStart)
 */
class Browse extends Model
{
	/**
	 * Get a listing of all Categories
	 *
	 * @return array
	 */
	public function getCategories()
	{
		// Get state variables
		$grouping = $this->getState('grouping', 'normal');
		$orderby  = $this->getState('orderby', 'order');

		$start = 0;
		$limit = 0;

		// Get all published categories
		/** @var Categories $catModel */
		$catModel = $this->container->factory->model('Categories')->tmpInstance();
		$catModel
			->limitstart($start)
			->limit($limit)
			->published(1)
			->access_user($this->container->platform->getUser()->id)
			->type('');

		/** @var \JApplicationSite $app */
		$app               = \JFactory::getApplication();
		$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

		if ($hasLanguageFilter)
		{
			$hasLanguageFilter = $app->getLanguageFilter();
		}

		if ($hasLanguageFilter)
		{
			$lang_filter_plugin = \JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new \JRegistry($lang_filter_plugin->params);

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg = \JFactory::getLanguage();
				$catModel->setState('language', $lg->getTag());
			}
			else
			{
				$catModel->setState('language', $app->input->getCmd('language', '*'));
			}
		}
		else
		{
			$catModel->setState('language', $app->input->getCmd('language', ''));
		}

		// Apply Visual Group filtering
		if ($app->isAdmin())
		{
			// It is possible that we are called in the back-end, due to Finder
			// trying to index the article we created/modified
			$params = \JRegistry::getInstance('com_ars');
			$vgroup = 0;
		}
		else
		{
			$params = $app->getParams('com_ars');

			// The vgroupid may be set as part of the request, prefer that and fall back to the params otherwise
			$vgroup = $app->input->getUint('vgroupid', $params->get('vgroupid', ''));
		}

		if ($vgroup)
		{
			$catModel->setState('vgroup', $vgroup);
		}

		// Apply ordering
		switch ($orderby)
		{
			case 'alpha':
				$catModel->setState('filter_order', 'title');
				$catModel->setState('filter_order_Dir', 'ASC');
				break;

			case 'ralpha':
				$catModel->setState('filter_order', 'title');
				$catModel->setState('filter_order_Dir', 'DESC');
				break;

			case 'created':
				$catModel->setState('filter_order', 'created');
				$catModel->setState('filter_order_Dir', 'ASC');
				break;

			case 'rcreated':
				$catModel->setState('filter_order', 'created');
				$catModel->setState('filter_order_Dir', 'DESC');
				break;

			case 'order':
				$catModel->setState('filter_order', 'ordering');
				$catModel->setState('filter_order_Dir', 'ASC');
				break;
		}

		$allCategories = $catModel->get();

		// Filter and return the list
		$list = Filter::filterList($allCategories);
		unset($allCategories);

		if ($grouping != 'none')
		{
			$allCategories = $list;
			$list          = array('normal' => array(), 'bleedingedge' => array());

			foreach ($allCategories as $cat)
			{
				$list[ $cat->type ][] = $cat;
			}
		}
		else
		{
			$list = array('all' => $list);
		}

		// TODO Do I need this?
		$this->setState('pagination', new \JPagination($catModel->count(), $catModel->limitstart, $catModel->limit));

		return $list;
	}

	/**
	 * Loads and returns a category definition
	 *
	 * @param int $id The Category ID to load
	 *
	 * @return Categories|null An instance of Categories, or null if the user shouldn't view the category
	 */
	public function getCategory($id = 0)
	{
		$this->setState('category_id', $id);

		/** @var Categories $catModel */
		$catModel = $this->container->factory->model('Categories')->tmpInstance();

		try
		{
			$cat = $catModel
				->access_user($this->container->platform->getUser()->id)
				->id($id)
				->firstOrFail();

		}
		catch (\Exception $e)
		{
			return null;
		}

		// Is it published?
		if (!$cat->published)
		{
			return null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = new Collection([$cat]);

		$dummy = Filter::filterList($dummy);

		if (!count($dummy))
		{
			return null;
		}

		// TODO Do I really need this?
		$this->setState('item', $cat);

		return $cat;
	}

	/**
	 * Get a list of all releases in a given category
	 *
	 * @param int $cat_id The category ID
	 *
	 * @return array
	 */
	public function getReleases($cat_id = 0)
	{
		// Does this category pass the filtering?
		$category = $this->getCategory($cat_id);

		if (is_null($category))
		{
			return null;
		}

		// Get state variables
		$orderBy = $this->getState('rel_orderby', 'order');

		// Get limits
		$start = $this->getState('limitstart', 0);
		/** @var \JApplicationSite $app */
		$app   = \JFactory::getApplication();
		$limit = $this->getState('limit', -1);

		if ($limit == -1)
		{
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
		}

		// Get all published releases
		/** @var Releases $model */
		$model = $this->container->factory->model('Releases')->tmpInstance();
		$model
			->limitstart($start)
			->limit($limit)
			->published(1)
			->access_user($this->container->platform->getUser()->id)
			->category($cat_id);

		if ($app->getLanguageFilter())
		{
			$lang_filter_plugin = \JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new \JRegistry($lang_filter_plugin->params);

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg = \JFactory::getLanguage();
				$model->setState('language', $lg->getTag());
			}
			else
			{
				$model->setState('language', $app->input->getCmd('language', '*'));
			}
		}
		else
		{
			$model->setState('language', $app->input->getCmd('language', ''));
		}

		// Apply ordering
		switch ($orderBy)
		{
			case 'alpha':
				$model->setState('filter_order', 'version');
				$model->setState('filter_order_Dir', 'ASC');
				break;
			case 'ralpha':
				$model->setState('filter_order', 'version');
				$model->setState('filter_order_Dir', 'DESC');
				break;
			case 'created':
				$model->setState('filter_order', 'created');
				$model->setState('filter_order_Dir', 'ASC');
				break;
			case 'rcreated':
				$model->setState('filter_order', 'created');
				$model->setState('filter_order_Dir', 'DESC');
				break;
			case 'order':
				$model->setState('filter_order', 'ordering');
				$model->setState('filter_order_Dir', 'ASC');
				break;
		}

		$allItems = $model->get();

		// Filter and return the list
		$list = Filter::filterList($allItems);

		// TODO Do I need this?
		$this->setState('relPagination', new \JPagination($model->count(), $model->limitstart, $model->limit));
		$this->setState('itemList', $list);

		return $list;
	}

	/**
	 * Loads and returns a release definition
	 *
	 * @param int $id The Release ID to load
	 *
	 * @return Releases|null An instance of Releases, or null if the user shouldn't view the release
	 */
	public function getRelease($id = 0)
	{
		$this->item = null;

		/** @var Releases $relModel */
		$relModel = $this->container->factory->model('Releases')->tmpInstance();

		try
		{
			/** @var Releases $item */
			$item = $relModel
				->access_user($this->container->platform->getUser()->id)
				->id($id)
				->firstOrFail();

		}
		catch (\Exception $e)
		{
			return null;
		}

		// Is it published?
		if (!$item->published)
		{
			return null;
		}

		// Does the category pass the level / subscriptions filter?
		$category = $item->category;
		$dummy    = new Collection([$category]);
		$dummy    = Filter::filterList($dummy);

		if (!count($dummy))
		{
			return null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = new Collection([$item]);
		$dummy = Filter::filterList($dummy);

		if (!count($dummy))
		{
			return null;
		}

		// TODO Do I need this?
		$this->setState('item', $item);

		return $item;
	}

	/**
	 * Get a list of all items in a given release
	 *
	 * @param   int $rel_id The release ID
	 *
	 * @return  array
	 */
	public function getItems($rel_id = 0)
	{
		// Does the release pass the filtering? It automatically checks the category access as well.
		$release = $this->getRelease($rel_id);

		if (empty($release))
		{
			return null;
		}

		// Get state variables
		$orderby = $this->getState('items_orderby', 'order');

		// Get limits
		$start = $this->getState('limitstart', 0);
		/** @var \JApplicationSite $app */
		$app   = \JFactory::getApplication();
		$limit = $this->getState('limit', -1);

		if ($limit == -1)
		{
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
		}

		// Get all published releases
		/** @var Items $model */
		$model = $this->container->factory->model('Items')->tmpInstance();

		$model
			->limitstart($start)
			->limit($limit)
			->published(1)
			->access_user($this->container->platform->getUser()->id)
			->release($rel_id);

		if ($app->getLanguageFilter())
		{
			$lang_filter_plugin = \JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new \JRegistry($lang_filter_plugin->params);

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg = \JFactory::getLanguage();
				$model->setState('language', $lg->getTag());
			}
			else
			{
				$model->setState('language', $app->input->getCmd('language', '*'));
			}
		}
		else
		{
			$model->setState('language', $app->input->getCmd('language', ''));
		}

		// Apply ordering
		switch ($orderby)
		{
			case 'alpha':
				$model->setState('filter_order', 'title');
				$model->setState('filter_order_Dir', 'ASC');
				break;
			case 'ralpha':
				$model->setState('filter_order', 'title');
				$model->setState('filter_order_Dir', 'DESC');
				break;
			case 'created':
				$model->setState('filter_order', 'created');
				$model->setState('filter_order_Dir', 'ASC');
				break;
			case 'rcreated':
				$model->setState('filter_order', 'created');
				$model->setState('filter_order_Dir', 'DESC');
				break;
			case 'order':
				$model->setState('filter_order', 'ordering');
				$model->setState('filter_order_Dir', 'ASC');
				break;
		}

		$allItems = $model->get();

		// Filter and return the list
		$list = Filter::filterList($allItems);

		// TODO Do I need this?
		$this->setState('items_pagination', new \JPagination($model->count(), $model->limitstart, $model->limit));
		$this->setState('itemList', $list);

		return $list;
	}

	public function processFeedData($orderby = 'order')
	{
		$this->itemList = $this->getCategories();

		if (!count($this->itemList))
		{
			return;
		}

		foreach ($this->itemList as $sectionName => $section)
		{
			if (!empty($section))
			{
				foreach ($section as $cat)
				{
					/** @var Releases $model */
					$model = $this->container->factory->model('Releases')->tmpInstance();

					$model
						->category($cat->id)
						->published(1)
						->access_user($this->container->platform->getUser()->id)
						->limitstart(0)
						->limit(1);

					switch ($orderby)
					{
						case 'alpha':
							$model->setState('filter_order', 'title');
							$model->setState('filter_order_Dir', 'ASC');
							break;

						case 'ralpha':
							$model->setState('filter_order', 'title');
							$model->setState('filter_order_Dir', 'DESC');
							break;

						case 'created':
							$model->setState('filter_order', 'created');
							$model->setState('filter_order_Dir', 'ASC');
							break;

						case 'rcreated':
							$model->setState('filter_order', 'created');
							$model->setState('filter_order_Dir', 'DESC');
							break;

						case 'order':
							$model->setState('filter_order', 'ordering');
							$model->setState('filter_order_Dir', 'ASC');
							break;
					}

					$model->setState('maturity', $this->getState('maturity', 'alpha'));
					/** @var \JApplicationSite $app */
					$app               = \JFactory::getApplication();
					$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

					if ($hasLanguageFilter)
					{
						$hasLanguageFilter = $app->getLanguageFilter();
					}

					if ($hasLanguageFilter)
					{
						$lang_filter_plugin = \JPluginHelper::getPlugin('system', 'languagefilter');
						$lang_filter_params = new \JRegistry($lang_filter_plugin->params);
						if ($lang_filter_params->get('remove_default_prefix'))
						{
							// Get default site language
							$lg = \JFactory::getLanguage();
							$model->setState('language', $lg->getTag());
						}
						else
						{
							$model->setState('language', $app->input->getCmd('language', '*'));
						}
					}
					else
					{
						$model->setState('language', $app->input->getCmd('language', ''));
					}

					$releases = $model->get();

					if (empty($releases))
					{
						$cat->release = null;
					}
					else
					{
						$cat->release = array_shift($releases);
					}
				}
			}
		}
	}

	public function processLatest()
	{
		/** @var \JApplicationSite $app */
		$app = \JFactory::getApplication();

		if ($app->isAdmin())
		{
			// It is possible that we are called in the back-end, due to Finder
			// trying to index the article we created/modified
			$params = \JRegistry::getInstance('com_ars');
		}
		else
		{
			$params = $app->getParams('com_ars');
		}

		$this->processFeedData($params->get('rel_orderby', 'order'));

		if (!count($this->itemList))
		{
			return;
		}

		foreach ($this->itemList as $sectionname => $section)
		{
			if (!empty($section))
			{
				foreach ($section as $cat)
				{
					if (empty($cat->release))
					{
						$cat->release = (object)array('id' => null, 'files' => null);

						continue;
					}

					/** @var Items $model */
					$model   = $this->container->factory->model('Items')->tmpInstance();
					$orderby = $params->get('items_orderby', 'order');

					switch ($orderby)
					{
						case 'alpha':
							$model->setState('filter_order', 'title');
							$model->setState('filter_order_Dir', 'ASC');
							break;

						case 'ralpha':
							$model->setState('filter_order', 'title');
							$model->setState('filter_order_Dir', 'DESC');
							break;

						case 'created':
							$model->setState('filter_order', 'created');
							$model->setState('filter_order_Dir', 'ASC');
							break;

						case 'rcreated':
							$model->setState('filter_order', 'created');
							$model->setState('filter_order_Dir', 'DESC');
							break;

						case 'order':
							$model->setState('filter_order', 'ordering');
							$model->setState('filter_order_Dir', 'ASC');
							break;
					}

					$model->setState('published', 1);
					$model->setState('release', $cat->release->id);
					$model->setState('limitstart', 0);
					$model->setState('limit', 0);

					$model->access_user($this->container->platform->getUser()->id);

					$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

					if ($hasLanguageFilter)
					{
						$hasLanguageFilter = $app->getLanguageFilter();
					}

					if ($hasLanguageFilter)
					{
						$lang_filter_plugin = \JPluginHelper::getPlugin('system', 'languagefilter');
						$lang_filter_params = new \JRegistry($lang_filter_plugin->params);

						if ($lang_filter_params->get('remove_default_prefix'))
						{
							// Get default site language
							$lg = \JFactory::getLanguage();
							$model->setState('language', $lg->getTag());
						}
						else
						{
							$model->setState('language', $app->input->getCmd('language', '*'));
						}
					}
					else
					{
						$model->setState('language', $app->input->getCmd('language', ''));
					}

					$rawlist             = $model->get();
					$cat->release->files = Filter::filterList($rawlist);
				}
			}
		}
	}
}