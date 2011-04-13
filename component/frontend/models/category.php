<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once dirname(__FILE__).DS.'base.php';

class ArsModelCategory extends ArsModelBaseFE
{
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
				$model->setState('order','version');
				$model->setState('dir','ASC');
				break;
			case 'ralpha':
				$model->setState('order','version');
				$model->setState('dir','DESC');
				break;
			case 'created':
				$model->setState('order','created_on');
				$model->setState('dir','ASC');
				break;
			case 'rcreated':
				$model->setState('order','created_on');
				$model->setState('dir','DESC');
				break;
			case 'order':
				$model->setState('order','ordering');
				$model->setState('dir','ASC');
				break;
		}

		$allItems = $model->getItemList();

		// Filter and return the list
		$list = $this->filterList($allItems);

		$this->pagination = $model->getPagination();
		$this->itemList = $list;

		return $list;
	}
}