<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

require_once JPATH_SITE.'/components/com_ars/helpers/filter.php';

class ArsModelBrowses extends FOFModel
{
	public function __construct($config = array()) {
		$config['table'] = 'category';
		parent::__construct($config);
	}
	
	/**
	 * Get a listing of all Categories
	 * @return array
	 */
	public function getCategories()
	{
		// Get state variables
		$grouping = $this->getState('grouping','normal');
		$orderby = $this->getState('orderby', 'order');

		$start = 0;
		$limit = 0;

		// Get all published categories
		$catModel = FOFModel::getTmpInstance('Categories','ArsModel')
			->limitstart($start)
			->limit($limit)
			->published(1)
			->type('');

		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$app = JFactory::getApplication();
			if($app->getLanguageFilter()) {
				$lang_filter_plugin = &JPluginHelper::getPlugin('system', 'languagefilter');
				$lang_filter_params = new JRegistry($lang_filter_plugin->params);
				if ($lang_filter_params->get('remove_default_prefix')) {
					// Get default site language
					$lg = &JFactory::getLanguage();
					$catModel->setState('language', $lg->getTag());
				}else{                                                                                                                 
					$catModel->setState('language', FOFInput::getCmd('language', '*', $this->input));
				}
				$catModel->setState('language', FOFInput::getCmd('language', '*', $this->input));
			} else {
				$catModel->setState('language', FOFInput::getCmd('language', '', $this->input));
			}
		}

		// Apply ordering
		switch($orderby)
		{
			case 'alpha':
				$catModel->setState('order','title');
				$catModel->setState('dir','ASC');
				break;

			case 'ralpha':
				$catModel->setState('order','title');
				$catModel->setState('dir','DESC');
				break;

			case 'created':
				$catModel->setState('order','created');
				$catModel->setState('dir','ASC');
				break;

			case 'rcreated':
				$catModel->setState('order','created');
				$catModel->setState('dir','DESC');
				break;

			case 'order':
				$catModel->setState('order','ordering');
				$catModel->setState('dir','ASC');
				break;

		}

		$allCategories = $catModel->getItemList();

		// Filter and return the list
		$list = ArsHelperFilter::filterList($allCategories);
		unset($allCategories);

		if($grouping != 'none') {
			$allCategories = $list;
			$list = array('normal' => array(), 'bleedingedge' => array());

			while(!empty($allCategories))
			{
				$cat = array_shift($allCategories);
				$list[$cat->type][] = $cat;
			}
		} else {
			$list = array('all' => $list);
		}

		return $list;
	}
	
	/**
	 * Loads and returns a category definition
	 * @param int $id The Category ID to load
	 * @return ArsTableCategory|null An instance of ArsTableCategory, or null if the user shouldn't view the category
	 */
	public function getCategory($id = 0)
	{
		$this->setState('category_id', $id);
		
		$cat = FOFModel::getTmpInstance('Categories','ArsModel')
			->getItem($id);
		
		// Is it published?
		if(!$cat->published) {
			return null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = $list = ArsHelperFilter::filterList(array($cat));
		if(!count($dummy)) {
			return null;
		}

		$this->item = $cat;		
		
		return $cat;
	}
	
	/**
	 * Get a list of all releases in a given category
	 * @param int $cat_id The category ID
	 * @return array
	 */
	public function getReleases($cat_id = 0)
	{
		// Get state variables
		$orderby = $this->getState('rel_orderby', 'order');

		// Get limits
		$start = $this->getState('limitstart', 0);
		$app = JFactory::getApplication();
		$limit = $this->getState('limit',-1);
		if($limit == -1) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		}

		// Get all published releases
		$model = FOFModel::getTmpInstance('Releases','ArsModel')
			->limitstart($start)
			->limit($limit)
			->published(1)
			->category($cat_id);
		
		$app = JFactory::getApplication();
		if($app->getLanguageFilter()) {
			$lang_filter_plugin = &JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);
			if ($lang_filter_params->get('remove_default_prefix')) {
				// Get default site language
				$lg = &JFactory::getLanguage();
				$model->setState('language', $lg->getTag());
			}else{                                                                                                                 
				$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
			}
			$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
		} else {
			$model->setState('language', FOFInput::getCmd('language', '', $this->input));
		}

		// Apply ordering
		switch($orderby)
		{
			case 'alpha':
				$model->setState('order','version');
				$model->setState('dir','ASC');
				break;
			case 'ralpha':
				$model->setState('order','version');
				$model->setState('dir','DESC');
				break;
			case 'created':
				$model->setState('order','created');
				$model->setState('dir','ASC');
				break;
			case 'rcreated':
				$model->setState('order','created');
				$model->setState('dir','DESC');
				break;
			case 'order':
				$model->setState('order','ordering');
				$model->setState('dir','ASC');
				break;
		}

		$allItems = $model->getItemList();

		// Filter and return the list
		$list = ArsHelperFilter::filterList($allItems);

		$this->relPagination = $model->getPagination();
		$this->itemList = $list;

		return $list;
	}

	
	/**
	 * Loads and returns a release definition
	 * @param int $id The Release ID to load
	 * @return ArsTableReleases|null An instance of ArsTableReleases, or null if the user shouldn't view the release
	 */
	public function getRelease($id = 0)
	{
		$this->item = null;

		$item = FOFModel::getTmpInstance('Releases','ArsModel')
			->getItem($id);

		// Is it published?
		if(!$item->published) {
			return null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = ArsHelperFilter::filterList( array($item) );
		if(!count($dummy)) return null;

		$this->item = $item;
		return $item;
	}

	/**
	 * Get a list of all items in a given release
	 * @param int $rel_id The release ID
	 * @return array
	 */
	public function getItems($rel_id = 0)
	{
		// Get state variables
		$orderby = $this->getState('items_orderby', 'order');

		// Get limits
		$start = $this->getState('start', 0);
		$app = JFactory::getApplication();
		$limit = $this->getState('limit',-1);
		if($limit == -1) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		}

		// Get all published releases
		$model = FOFModel::getTmpInstance('Items','ArsModel')
			->limitstart($start)
			->limit($limit)
			->published(1)
			->release($rel_id);
		$app = JFactory::getApplication();
		if($app->getLanguageFilter()) {
			$lang_filter_plugin = &JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);
			if ($lang_filter_params->get('remove_default_prefix')) {
				// Get default site language
				$lg = &JFactory::getLanguage();
				$model->setState('language', $lg->getTag());
			}else{                                                                                                                 
				$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
			}
			$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
		} else {
			$model->setState('language', FOFInput::getCmd('language', '', $this->input));
		}

		// Apply ordering
		switch($orderby)
		{
			case 'alpha':
				$model->setState('order','title');
				$model->setState('dir','ASC');
				break;
			case 'ralpha':
				$model->setState('order','title');
				$model->setState('dir','DESC');
				break;
			case 'created':
				$model->setState('order','created');
				$model->setState('dir','ASC');
				break;
			case 'rcreated':
				$model->setState('order','created');
				$model->setState('dir','DESC');
				break;
			case 'order':
				$model->setState('order','ordering');
				$model->setState('dir','ASC');
				break;
		}

		$allItems = $model->getItemList();

		// Filter and return the list
		$list = ArsHelperFilter::filterList($allItems);

		$this->items_pagination = $model->getPagination();
		$this->itemList = $list;

		return $list;
	}
	
	public function processFeedData($orderby = 'order')
	{
		$this->itemList = $this->getCategories();

		if(!count($this->itemList)) return;

		foreach($this->itemList as $sectionname => $section)
		{
			if(!empty($section)) foreach($section as $cat)
			{
				$model = FOFModel::getTmpInstance('Releases','ArsModel')
					->category($cat->id)
					->published(1)
					->limitstart(0)
					->limit(1);
				switch($orderby)
				{
					case 'alpha':
						$model->setState('order','title');
						$model->setState('dir','ASC');
						break;

					case 'ralpha':
						$model->setState('order','title');
						$model->setState('dir','DESC');
						break;

					case 'created':
						$model->setState('order','created');
						$model->setState('dir','ASC');
						break;

					case 'rcreated':
						$model->setState('order','created');
						$model->setState('dir','DESC');
						break;

					case 'order':
						$model->setState('order','ordering');
						$model->setState('dir','ASC');
						break;

				}
				$model->setState('maturity',		$this->getState('maturity','alpha'));
				$app = JFactory::getApplication();
				if($app->getLanguageFilter()) {
					$lang_filter_plugin = &JPluginHelper::getPlugin('system', 'languagefilter');
					$lang_filter_params = new JRegistry($lang_filter_plugin->params);
					if ($lang_filter_params->get('remove_default_prefix')) {
						// Get default site language
						$lg = &JFactory::getLanguage();
						$model->setState('language', $lg->getTag());
					}else{                                                                                                                 
						$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
					}
					$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
				} else {
					$model->setState('language', FOFInput::getCmd('language', '', $this->input));
				}
				
				$releases = $model->getItemList();

				if(empty($releases)) {
					$cat->release = null;
				} else {
					$cat->release = array_shift($releases);
				}
			}
		}
	}

	public function processLatest()
	{
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');
		
		$this->processFeedData($params->get('rel_orderby', 'order'));

		if(!count($this->itemList)) return;

		foreach($this->itemList as $sectionname => $section)
		{
			if(!empty($section)) foreach($section as $cat)
			{
				if(empty($cat->release)) {
					$cat->release = (object)array('id'=>null, 'files' => null);
					continue;
				}

				$model = FOFModel::getTmpInstance('Items','ArsModel');

				$orderby = $params->get('items_orderby',	'order');
				switch($orderby)
				{
					case 'alpha':
						$model->setState('order','title');
						$model->setState('dir','ASC');
						break;

					case 'ralpha':
						$model->setState('order','title');
						$model->setState('dir','DESC');
						break;

					case 'created':
						$model->setState('order','created');
						$model->setState('dir','ASC');
						break;

					case 'rcreated':
						$model->setState('order','created');
						$model->setState('dir','DESC');
						break;

					case 'order':
						$model->setState('order','ordering');
						$model->setState('dir','ASC');
						break;

				}
				
				$model->setState('published',		1);
				$model->setState('release',			$cat->release->id);
				$model->setState('limitstart',		0);
				$model->setState('limit',			0);
                
				if($app->getLanguageFilter()) {
					$lang_filter_plugin = &JPluginHelper::getPlugin('system', 'languagefilter');
					$lang_filter_params = new JRegistry($lang_filter_plugin->params);
					if ($lang_filter_params->get('remove_default_prefix')) {
						// Get default site language
						$lg = &JFactory::getLanguage();
						$model->setState('language', $lg->getTag());
					}else{                                                                                                                 
						$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
					}
					$model->setState('language', FOFInput::getCmd('language', '*', $this->input));
				} else {
					$model->setState('language', FOFInput::getCmd('language', '', $this->input));
				}
				
				$rawlist = $model->getItemList();
				$cat->release->files = ArsHelperFilter::filterList($rawlist);
			}
		}
	}
}