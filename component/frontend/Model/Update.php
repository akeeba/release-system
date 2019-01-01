<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die;

use FOF30\Model\Model;

class Update extends Model
{
	public function getCategoryItems($category)
	{
		$db = $this->container->db;

		$query = $db->getQuery(true)
					->select(array(
						$db->qn('u') . '.*',
						$db->qn('i') . '.' . $db->qn('id') . ' AS ' . $db->qn('item_id'),
						$db->qn('r') . '.' . $db->qn('version'),
						$db->qn('r') . '.' . $db->qn('maturity'),
					))
					->from($db->qn('#__ars_items') . ' AS ' . $db->qn('i'))
					->innerJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r') . ' ON(' .
						$db->qn('r') . '.' . $db->qn('id') . ' = ' . $db->qn('i') . '.' . $db->qn('release_id')
						. ')')
					->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
						$db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->qn('r') . '.' . $db->qn('category_id')
						. ')')
					->join('LEFT OUTER', $db->qn('#__ars_updatestreams') . ' AS ' . $db->qn('u') . ' ON(' .
						$db->qn('u') . '.' . $db->qn('id') . ' = ' . $db->qn('i') . '.' . $db->qn('updatestream')
						. ')')
					->where($db->qn('u') . '.' . $db->qn('type') . ' = ' . $db->q($category))
					->where($db->qn('u') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('i') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('r') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('c') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					/*
					->group(array(
						$db->qn('u').'.'.$db->qn('id')
					))
					*/
					->order(array(
				$db->qn('u') . '.' . $db->qn('id') . ' ASC',
				$db->qn('i') . '.' . $db->qn('created') . ' DESC'
			));
		$db->setQuery($query);

		$temp  = $db->loadObjectList();
		$items = array();

		// Loop results, keep only the first row with the same 'id' column
		if (!empty($temp))
		{
			$processed = array();

			foreach ($temp as $row)
			{
				if (in_array($row->id, $processed))
				{
					continue;
				}
				$processed[] = $row->id;
				$items[]     = $row;
			}

			unset($processed);
		}

		unset($temp);

		return $items;
	}

	public function getItems($id)
	{
		$db = $this->container->db;

		$query = $db->getQuery(true)
					->select(array(
						$db->qn('u') . '.*',
						$db->qn('i') . '.' . $db->qn('id') . ' AS ' . $db->qn('item_id'),
						$db->qn('i') . '.' . $db->qn('environments') . ' AS ' . $db->qn('environments'),
						$db->qn('i') . '.' . $db->qn('md5'),
						$db->qn('i') . '.' . $db->qn('sha1'),
						$db->qn('i') . '.' . $db->qn('sha256'),
						$db->qn('i') . '.' . $db->qn('sha384'),
						$db->qn('i') . '.' . $db->qn('sha512'),
						$db->qn('r') . '.' . $db->qn('version'),
						$db->qn('r') . '.' . $db->qn('maturity'),
						$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('cat_title'),
						$db->qn('i') . '.' . $db->qn('release_id'),
						$db->qn('i') . '.' . $db->qn('filename'),
						$db->qn('i') . '.' . $db->qn('url'),
						$db->qn('i') . '.' . $db->qn('type') . ' AS ' . $db->qn('itemtype'),
						$db->qn('r') . '.' . $db->qn('created'),
						$db->qn('r') . '.' . $db->qn('notes') . ' AS ' . $db->qn('release_notes')
					))
					->from($db->qn('#__ars_items') . ' AS ' . $db->qn('i'))
					->innerJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r') . ' ON(' .
						$db->qn('r') . '.' . $db->qn('id') . ' = ' . $db->qn('i') . '.' . $db->qn('release_id')
						. ')')
					->innerJoin($db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
						$db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->qn('r') . '.' . $db->qn('category_id')
						. ')')
					->join('RIGHT', $db->qn('#__ars_updatestreams') . ' AS ' . $db->qn('u') . ' ON(' .
						$db->qn('u') . '.' . $db->qn('id') . ' = ' . $db->qn('i') . '.' . $db->qn('updatestream')
						. ')')
					->where($db->qn('u') . '.' . $db->qn('id') . ' = ' . $db->q($id))
					->where($db->qn('u') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('i') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('r') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->where($db->qn('c') . '.' . $db->qn('published') . ' = ' . $db->q('1'))
					->order($db->qn('r') . '.' . $db->qn('created') . ' DESC');
		$db->setQuery($query);

		$ret = $db->loadObjectList();

		if (is_array($ret) && !empty($ret))
		{
			foreach ($ret as &$item)
			{
				$environments = $item->environments;
				$environments = empty($environments) ? array() : json_decode($environments);
				$environments = empty($environments) ? array() : $environments;
				$item->environments = $environments;
			}
		}

		return $ret;
	}

	public function getPublished($id)
	{
		$db = $this->container->db;

		$query = $db->getQuery(true)
					->select($db->qn('published'))
					->from($db->qn('#__ars_updatestreams'))
					->where($db->qn('id') . ' = ' . $db->q($id));

		$db->setQuery($query);

		return $db->loadResult();
	}
}
