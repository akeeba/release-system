<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelBrowse extends JModel
{
	var $itemList = null;
	var $item = null;
	var $lists = null;
	var $pagination = null;

	/**
	 * Get a listing of all Categories
	 * @return array
	 */
	public function getCategories()
	{
		// Get state variables
		$grouping = $this->getState('grouping','normal');
		$orderby = $this->getState('orderby', 'order');

		// Get limits
		/*
		$start = $this->getState('start', 0);
		$app = JFactory::getApplication();
		$limit = $this->getState('limit',-1);
		if($limit == -1) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		}
		*/
		$start = 0;
		$limit = 0;

		// Get all published categories
		$catModel = JModel::getInstance('Categories','ArsModel');
		$catModel->reset();
		$catModel->setState('limitstart',$start);
		$catModel->setState('limit',$limit);
		$catModel->setState('published',1);

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
				$catModel->setState('order','created_on');
				$catModel->setState('dir','ASC');
				break;

			case 'rcreated':
				$catModel->setState('order','created_on');
				$catModel->setState('dir','DESC');
				break;

			case 'order':
				$catModel->setState('order','ordering');
				$catModel->setState('dir','ASC');
				break;

		}

		$allCategories = $catModel->getItemList();

		// Filter and return the list
		$list = $this->filterList($allCategories);
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
	 * @return TableCategories|null An instance of TableCategories, or null if the user shouldn't view the category
	 */
	public function getCategory($id)
	{
		$catModel = JModel::getInstance('Categories','ArsModel');
		$catModel->reset();
		$catModel->setId($id);
		$cat = $catModel->getItem();

		// Is it published?
		if(!$cat->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($cat) );
		if(!count($dummy)) return null;

		return $cat;
	}

	/**
	 * Get a list of all releases in a given category
	 * @param int $cat_id The category ID
	 * @return array
	 */
	public function getReleases($cat_id)
	{
		// Get all published categories
		$model = JModel::getInstance('Releases','ArsModel');
		$model->reset();
		$model->setState('limitstart',0);
		$model->setState('limit',0);
		$model->setState('published',1);
		$model->setState('category',$cat_id);
		$allItems = $model->getItemList();

		// Filter and return the list
		$list = $this->filterList($allItems);
		return $list;
	}

	/**
	 * Loads and returns a release definition
	 * @param int $id The Release ID to load
	 * @return TableReleases|null An instance of TableReleases, or null if the user shouldn't view the release
	 */
	public function getRelease($id)
	{
		$model = JModel::getInstance('Releases','ArsModel');
		$model->reset();
		$model->setId($id);
		$item = $model->getItem();

		// Is it published?
		if(!$item->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($item) );
		if(!count($dummy)) return null;

		return $item;
	}

	/**
	 * Get a list of all items in a given release
	 * @param int $rel_id The release ID
	 * @return array
	 */
	public function getItems($rel_id)
	{
		// Get all published categories
		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setState('limitstart',0);
		$model->setState('limit',0);
		$model->setState('published',1);
		$model->setState('release',$rel_id);
		$allItems = $model->getItemList();

		// Filter and return the list
		$list = $this->filterList($allItems);
		return $list;
	}

	/**
	 * Loads and returns an item definition
	 * @param int $id The Item ID to load
	 * @return TableItems|null An instance of TableItems, or null if the user shouldn't view the item
	 */
	public function getItem($id)
	{
		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setId($id);
		$item = $model->getItem();

		// Is it published?
		if(!$item->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($item) );
		if(!count($dummy)) return null;

		return $item;
	}

	/**
	 * Filters a list by access level and AMBRA.subs groups
	 * @param array $source The source list
	 * @return array The filtered list
	 */
	private function filterList($source)
	{
		static $user_access = null;
		static $myGroups = null;

		// Initialise filtered list
		$list = array();

		// Check for empty source lists
		if(!is_array($source)) return $list;
		if(empty($source)) return $list;

		// Short-circuit if AMBRA.subs is not present
		$groupModel = JModel::getInstance('Ambra','ArsModel');
		if(!ArsModelAmbra::hasAMBRA()) return $source;

		// Cache user access and groups
		if(is_null($user_access) || is_null($myGroups))
		{
			// Get user info
			$user = JFactory::getUser();
			$user_access = $user->aid;

			// Get AMBRA groups of current user
			$mygroups = $groupModel->getUserGroups();
		}

		// Do the real filtering
		foreach($source as $s)
		{
			// Filter by access level
			if($s->access > $user_access) continue;

			// Filter by AMBRA.subs group
			if(!empty($s->groups))
			{
				// Category defines AMBRA.subs groups, user belongs to none, do
				// not display anything.
				if(empty($mygroups)) continue;

				// Check if any of the category's AMBRA.subs groups are in the
				// list of groups the user belongs to
				$groups = explode(',', $s->groups);
				$inGroups = false;
				if(!empty($groups)) foreach($groups as $group)
				{
					if(in_array($group, $mygroups)) $inGroups = true;
				}
				else
				{
					$inGroups = true;
				}
				if(!$inGroups) continue;
			}

			$list[] = $s;
		}

		return $list;
	}
}