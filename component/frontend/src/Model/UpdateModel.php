<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

class UpdateModel extends BaseDatabaseModel
{
	public function getCategoryItems(string $category): ?array
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select(array(
				$db->quoteName('u') . '.*',
				$db->quoteName('i.id', 'item_id'),
				$db->quoteName('r.version'),
				$db->quoteName('r.maturity'),
			))
			->from($db->quoteName('#__ars_items', 'i'))
			->join('INNER',
				$db->quoteName('#__ars_updatestreams', 'u'),
				$db->quoteName('u.id') . ' = ' . $db->quoteName('i.updatestream') . ' AND ' .
				$db->quoteName('u.type') . ' = :category AND ' .
				$db->quoteName('u.published') . ' = 1 AND ' .
				$db->quoteName('i.published') . ' = 1'
			)
			->join('LEFT',
				$db->quoteName('#__ars_releases', 'r'),
				$db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id') . ' AND ' .
				$db->quoteName('r.published') . ' = 1'
			)
			->join('LEFT',
				$db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id') . ' AND ' .
				$db->quoteName('c.published') . ' = 1'
			)
			->order(array(
				$db->quoteName('u.id') . ' ASC',
				$db->quoteName('i.created') . ' DESC'
			))
			->bind(':category', $category)
		;

		$temp  = $db->setQuery($query)->loadObjectList();
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

	public function getItems(int $id): ?array
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select(array(
				$db->quoteName('u') . '.*',
				$db->quoteName('i.id', 'item_id'),
				$db->quoteName('i.environments', 'environments'),
				$db->quoteName('i.md5'),
				$db->quoteName('i.sha1'),
				$db->quoteName('i.sha256'),
				$db->quoteName('i.sha384'),
				$db->quoteName('i.sha512'),
				$db->quoteName('r.version'),
				$db->quoteName('r.maturity'),
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('i.release_id'),
				$db->quoteName('i.filename'),
				$db->quoteName('i.url'),
				$db->quoteName('i.type', 'itemtype'),
				$db->quoteName('r.created'),
				$db->quoteName('r.notes', 'release_notes')
			))
			->from($db->quoteName('#__ars_items', 'i'))
			->innerJoin($db->quoteName('#__ars_releases', 'r'),
				$db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id'))
			->innerJoin($db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id'))
			->join('RIGHT', $db->quoteName('#__ars_updatestreams', 'u'),
				$db->quoteName('u.id') . ' = ' . $db->quoteName('i.updatestream'))
			->where($db->quoteName('u.id') . ' = :id')
			->where($db->quoteName('u.published') . ' = 1')
			->where($db->quoteName('i.published') . ' = 1')
			->where($db->quoteName('r.published') . ' = 1')
			->where($db->quoteName('c.published') . ' = 1')
			->order($db->quoteName('r.created') . ' DESC')
			->bind(':id', $id, ParameterType::INTEGER);

		$ret = $db->setQuery($query)->loadObjectList();

		// Order updates by version, listing the latest version on top.
		usort($ret, function ($a, $b) {
			return version_compare($b->version, $a->version);
		});

		if (is_array($ret) && !empty($ret))
		{
			foreach ($ret as &$item)
			{
				$environments       = $item->environments;
				$environments       = empty($environments) ? [] : json_decode($environments);
				$environments       = empty($environments) ? [] : $environments;
				$item->environments = $environments;
			}
		}

		return $ret;
	}

	public function getPublished(int $id): ?string
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('published'))
			->from($db->quoteName('#__ars_updatestreams'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER)
		;

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function getCategoryAliasForUpdateId($id): ?string
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('c.alias'))
			->from($db->quoteName('#__ars_updatestreams', 'u'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('u.category')
			)
			->where($db->quoteName('u.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadResult() ?: null;
	}
}