<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelCategory extends JModel
{
	var $itemList = null;
	var $item = null;
	var $lists = null;
	var $pagination = null;

	/**
	 * Loads and returns a category definition
	 * @param int $id The Category ID to load
	 * @return TableCategories|null An instance of TableCategories, or null if the user shouldn't view the category
	 */
	public function getCategory($id = 0)
	{
		$this->item = null;

		$catModel = JModel::getInstance('Categories','ArsModel');
		$catModel->reset();
		$catModel->setId($id);
		$cat = $catModel->getItem();

		// Is it published?
		if(!$cat->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($cat) );
		if(!count($dummy)) return null;

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
		$start = $this->getState('start', 0);
		$app = JFactory::getApplication();
		$limit = $this->getState('limit',-1);
		if($limit == -1) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		}

		// Get all published releases
		$model = JModel::getInstance('Releases','ArsModel');
		$model->reset();
		$model->setState('limitstart',$start);
		$model->setState('limit',$limit);
		$model->setState('published',1);
		$model->setState('category',$cat_id);

		// Apply ordering
		switch($orderby)
		{
			case 'alpha':
				$catModel->setState('order','version');
				$catModel->setState('dir','ASC');
				break;
			case 'ralpha':
				$catModel->setState('order','version');
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

		$allItems = $model->getItemList();

		// Filter and return the list
		$list = $this->filterList($allItems);

		$this->pagination = $model->getPagination();
		$this->itemList = $list;

		return $list;
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