<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once dirname(__FILE__).DS.'base.php';

class ArsModelRelease extends ArsModelBaseFE
{
	/**
	 * Loads and returns a release definition
	 * @param int $id The Release ID to load
	 * @return TableReleases|null An instance of TableReleases, or null if the user shouldn't view the release
	 */
	public function getRelease($id = 0)
	{
		$this->item = null;

		$model = JModel::getInstance('Releases','ArsModel');
		$model->reset();
		$model->setId($id);
		$item = $model->getItem();

		// Is it published?
		if(!$item->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($item) );
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
		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setState('limitstart',$start);
		$model->setState('limit',$limit);
		$model->setState('published',1);
		$model->setState('release',$rel_id);

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