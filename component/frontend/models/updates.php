<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelUpdates extends F0FModel
{
	public $items;

	public function __construct($config = array()) {
		$config['table'] = 'updatestream';
		parent::__construct($config);
	}

	public function getCategoryItems($category)
	{
		$db = $this->getDBO();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('u').'.*',
				$db->qn('i').'.'.$db->qn('id').' AS '.$db->qn('item_id'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
			))
			->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
			->innerJoin($db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
				$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id')
				.')')
			->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
				$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id')
				.')')
			->join('LEFT OUTER', $db->qn('#__ars_updatestreams').' AS '.$db->qn('u').' ON('.
					$db->qn('u').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('updatestream')
					.')')
			->where($db->qn('u').'.'.$db->qn('type').' = '.$db->q($category))
			->where($db->qn('u').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('i').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('r').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('c').'.'.$db->qn('published').' = '.$db->q('1'))
			/*
			->group(array(
				$db->qn('u').'.'.$db->qn('id')
			))
			*/
			->order(array(
				$db->qn('u').'.'.$db->qn('id').' ASC',
				$db->qn('i').'.'.$db->qn('created').' DESC'
			))
		;
		$db->setQuery($query);

		$temp = $db->loadObjectList();
		$this->items = array();

		// Loop results, keep only the first row with the same 'id' column
		if(!empty($temp)) {
			$processed = array();
			foreach($temp as $row) {
				if(in_array($row->id, $processed)) continue;
				$processed[] = $row->id;
				$this->items[] = $row;
			}
			unset($processed);
		}
		unset($temp);
	}

	public function getItems($id)
	{
		$db = $this->getDBO();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('u').'.*',
				$db->qn('i').'.'.$db->qn('id').' AS '.$db->qn('item_id'),
				$db->qn('i').'.'.$db->qn('environments').' AS '.$db->qn('environments'),
				$db->qn('i').'.'.$db->qn('md5'),
				$db->qn('i').'.'.$db->qn('sha1'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
				$db->qn('i').'.'.$db->qn('release_id'),
				$db->qn('i').'.'.$db->qn('filename'),
				$db->qn('i').'.'.$db->qn('url'),
				$db->qn('i').'.'.$db->qn('type').' AS '.$db->qn('itemtype'),
				$db->qn('r').'.'.$db->qn('created'),
				$db->qn('r').'.'.$db->qn('notes').' AS '.$db->qn('release_notes')
			))
			->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
			->innerJoin($db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
					$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id')
					.')')
			->innerJoin($db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
					$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id')
					.')')
			->join('RIGHT', $db->qn('#__ars_updatestreams').' AS '.$db->qn('u').' ON('.
					$db->qn('u').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('updatestream')
					.')')
			->where($db->qn('u').'.'.$db->qn('id').' = '.$db->q($id))
			->where($db->qn('u').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('i').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('r').'.'.$db->qn('published').' = '.$db->q('1'))
			->where($db->qn('c').'.'.$db->qn('published').' = '.$db->q('1'))
			->order($db->qn('r').'.'.$db->qn('created').' DESC')
		;
		$db->setQuery($query);

		$this->items = $db->loadObjectList();
	}

	public function getPublished($id)
	{
		$db = $this->getDBO();

		$query = $db->getQuery(true)
			->select($db->qn('published'))
			->from($db->qn('#__ars_updatestreams'))
			->where($db->qn('id').' = '.$db->q($id));
		$db->setQuery($query);
		$this->published = $db->loadResult();
	}
}
