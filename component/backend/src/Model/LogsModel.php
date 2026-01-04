<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\Query\QueryElement;
use Joomla\Database\QueryInterface;

#[\AllowDynamicProperties]
class LogsModel extends ListModel
{
	protected static $catRelMap = [];

	protected static $relItemMap = [];

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'id',
				'user_id',
				'item_id',
				'accessed_on',
				'referer',
				'ip',
				'authorized',
				'search',
				'dlid',
				'published'
			];
		}

		parent::__construct($config, $factory);
	}

	public function getCategoryFromRelease(int $releaseId)
	{
		if (isset(self::$catRelMap[$releaseId]))
		{
			return self::$catRelMap[$releaseId];
		}

		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load($releaseId))
		{
			self::$catRelMap[$releaseId] = null;
		}
		else
		{
			self::$catRelMap[$releaseId] = $release->category_id;
		}

		return self::$catRelMap[$releaseId];
	}

	public function getReleaseFromItem(int $itemId)
	{
		if (isset(self::$relItemMap[$itemId]))
		{
			return self::$relItemMap[$itemId];
		}

		/** @var ItemTable $item */
		$item = $this->getMVCFactory()->createTable('Item', 'Administrator');

		if (!$item->load($itemId))
		{
			self::$relItemMap[$itemId] = null;
		}
		else
		{
			self::$relItemMap[$itemId] = $item->release_id;
		}

		return self::$relItemMap[$itemId];
	}

	public function applyDownloadItemsMeta(array &$items)
	{
		if (empty($items))
		{
			return;
		}

		$itemIds = array_unique(
			array_map(fn(object $item) => $item->item_id, $items)
		);

		if (empty($itemIds))
		{
			return;
		}

		$db = $this->getDatabase();
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query
			->select(
				[
					$db->quoteName('i.id', 'item_id'),
					$db->quoteName('i.title', 'item_title'),
					$db->quoteName('i.alias', 'item_alias'),
					$db->quoteName('r.id', 'rel_id'),
					$db->quoteName('r.version', 'rel_version'),
					$db->quoteName('r.alias', 'rel_alias'),
					$db->quoteName('c.id', 'cat_id'),
					$db->quoteName('c.title', 'cat_title'),
					$db->quoteName('c.alias', 'cat_alias'),
				]
			)
			->from($db->quoteName('#__ars_items', 'i'))
			->leftJoin(
				$db->quoteName('#__ars_releases', 'r'),
				$db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id'),
			)
			->leftJoin(
				$db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id'),
			);
		$query->whereIn($db->quoteName('i.id'), $itemIds);

		$itemMeta = $db->setQuery($query)->loadObjectList('item_id');

		if (empty($itemMeta))
		{
			return;
		}

		foreach ($items as $item)
		{
			$meta              = $itemMeta[$item->item_id] ?? null;
			$item->item_title  = $meta?->item_title;
			$item->item_alias  = $meta?->item_alias;
			$item->rel_id      = $meta?->rel_id;
			$item->rel_version = $meta?->rel_version;
			$item->rel_alias   = $meta?->rel_alias;
			$item->cat_id      = $meta?->cat_id;
			$item->cat_title   = $meta?->cat_title;
			$item->cat_alias   = $meta?->cat_alias;
		}
	}

	public function applyUserMeta(array &$items): void
	{
		if (empty($items))
		{
			return;
		}

		$userIds = array_unique(
			array_map(fn(object $user) => $user->user_id, $items)
		);

		if (empty($userIds))
		{
			return;
		}

		$db = $this->getDatabase();
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query
			->select(
				[
					$db->quoteName('id'),
					$db->quoteName('name', 'user_fullname'),
					$db->quoteName('username', 'user_username'),
					$db->quoteName('email', 'user_email'),
				]
			)
			->from($db->quoteName('#__users'));
		$query->whereIn($db->quoteName('id'), $userIds);

		$userMeta = $db->setQuery($query)->loadObjectList('id');

		if (empty($userMeta))
		{
			return;
		}

		foreach ($items as $item)
		{
			$meta                = $userMeta[$item->user_id] ?? null;
			$item->user_fullname = $meta?->user_fullname;
			$item->user_username = $meta?->user_username;
			$item->user_email    = $meta?->user_email;
		}
	}

	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$filters = [
			'search'      => 'string',
			'user'        => 'string',
			'referer'     => 'string',
			'user_id'     => 'int',
			'category_id' => 'int',
			'release_id'  => 'int',
			'item_id'     => 'int',
			'authorized'  => 'int',
		];

		foreach ($filters as $name => $type)
		{
			$value = $app->getUserStateFromRequest(
				$this->context . 'filter.' . $name,
				'filter_' . $name, '', $type
			);

			switch ($type)
			{
				case 'string':
					$this->setState('filter.' . $name, $value);
					break;

				case 'int':
					$this->setState('filter.' . $name, ($value === '') ? $value : (int) $value);
					break;
			}
		}

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.user');
		$id .= ':' . $this->getState('filter.referer');
		$id .= ':' . $this->getState('filter.user_id');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.release_id');
		$id .= ':' . $this->getState('filter.item_id');
		$id .= ':' . $this->getState('filter.authorized');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		/**
		 * l => #__ars_logs
		 * i => #__ars_items
		 * r => #__ars_releases
		 * c => #__ars_categories
		 * u => #__users
		 */

		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('*')
			->from($db->qn('#__ars_log'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$searchOperator = str_contains($search, '%') ? 'LIKE' : '=';
				$search         = trim($search);

				$query->where($db->quoteName('ip') . ' ' . $searchOperator . ' :ip')
					->bind(':ip', $search);
			}
		}

		// User ID filter
		$user_id = $this->getState('filter.user_id');

		if (is_numeric($user_id))
		{
			$user_id = (int) $user_id;
			$query->where($db->quoteName('user_id') . ' = :user_id')
				->bind(':user_id', $user_id);
		}

		// Referer filter
		$referer = $this->getState('filter.referer');

		if (!empty($referer))
		{
			$referer = '%' . $referer . '%';

			$query->where($db->quoteName('referer') . ' LIKE :referer')
				->bind(':referer', $referer);
		}

		// filter.authorized
		$authorized = $this->getState('filter.authorized');

		if (is_numeric($authorized))
		{
			$authorized = (int) $authorized;
			$query->where($db->quoteName('authorized') . ' = :authorized')
				->bind(':authorized', $authorized);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'i.id');
		$orderDirn = $this->state->get('list.direction', 'desc');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}