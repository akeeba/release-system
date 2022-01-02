<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
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

class LogsModel extends ListModel
{
	protected static $catRelMap = [];

	protected static $relItemMap = [];

	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'id', 'l.id',
				'item_id', 'i.id',
				'release_id', 'r.id',
				'category_id', 'c.id',
				'user_id', 'u.id',
				'referer', 'l.referer',
				'ip', 'l.ip',
				'accessed_on', 'l.accessed_on',
				'since', 'after',
				'authorized',
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

	protected function populateState($ordering = 'l.id', $direction = 'desc')
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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('l') . '.*',
				$db->quoteName('i.id', 'item_id'),
				$db->quoteName('i.title', 'item_title'),
				$db->quoteName('i.alias', 'item_alias'),
				$db->quoteName('r.id', 'rel_id'),
				$db->quoteName('r.version', 'rel_version'),
				$db->quoteName('r.alias', 'rel_alias'),
				$db->quoteName('c.id', 'cat_id'),
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('c.alias', 'cat_alias'),
				$db->quoteName('u.name', 'user_fullname'),
				$db->quoteName('u.username', 'user_username'),
				$db->quoteName('u.email', 'user_email'),
			])
			->from($db->qn('#__ars_log', 'l'))
			->join('LEFT', $db->qn('#__ars_items', 'i'), $db->quoteName('i.id') . ' = ' . $db->quoteName('l.item_id'))
			->join('LEFT', $db->qn('#__ars_releases', 'r'), $db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id'))
			->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('l.user_id'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('l.id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$ip = '%' . $search . '%';

				$query->where($db->quoteName('l.ip') . ' LIKE :ip')
					->bind(':ip', $ip);
			}
		}

		// User search filter
		$user = $this->getState('filter.user');

		if (!empty($user))
		{
			$user = '%' . $user . '%';

			$query->where(
				'(' .
				$db->quoteName('u.name') . ' LIKE :user1 OR ' .
				$db->quoteName('u.username') . ' LIKE :user2 OR ' .
				$db->quoteName('u.email') . ' LIKE :user3' .
				')')
				->bind(':user1', $user)
				->bind(':user2', $user)
				->bind(':user3', $user);
		}

		// Referer filter
		$referer = $this->getState('filter.referer');

		if (!empty($referer))
		{
			$referer = '%' . $referer . '%';

			$query->where($db->quoteName('l.referer') . ' LIKE :referer')
				->bind(':referer', $referer);
		}

		// User ID filter
		$user_id = $this->getState('filter.user_id');

		if (is_numeric($user_id))
		{
			$user_id = (int) $user_id;
			$query->where($db->quoteName('user_id') . ' = :user_id')
				->bind(':user_id', $user_id);
		}

		// Category ID filter
		$category_id = $this->getState('filter.category_id');

		if (is_numeric($category_id))
		{
			$category_id = (int) $category_id;
			$query->where($db->quoteName('c.id') . ' = :category_id')
				->bind(':category_id', $category_id);
		}

		// Release ID filter
		$release_id = $this->getState('filter.release_id');

		if (is_numeric($release_id))
		{
			$release_id = (int) $release_id;
			$query->where($db->quoteName('r.id') . ' = :release_id')
				->bind(':release_id', $release_id);
		}

		// Item ID filter
		$item_id = $this->getState('filter.item_id');

		if (is_numeric($item_id))
		{
			$item_id = (int) $item_id;
			$query->where($db->quoteName('l.item_id') . ' = :item_id')
				->bind(':item_id', $item_id);
		}

		// TODO filter.authorized
		$authorized = $this->getState('filter.authorized');

		if (is_numeric($authorized))
		{
			$authorized = (int) $authorized;
			$query->where($db->quoteName('l.authorized') . ' = :authorized')
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