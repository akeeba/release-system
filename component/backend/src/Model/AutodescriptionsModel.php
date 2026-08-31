<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ModelEnvironmentFilterTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

#[\AllowDynamicProperties]
class AutodescriptionsModel extends ListModel
{
	use ModelEnvironmentFilterTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			// Note: misleadingly named, the filter_fields must also include the sort by fields.
			$config['filter_fields'] = [
				'search',
				'id', 'a.id',
				'category_id', 'c.id',
				'title', 'a.title',
				'created', 'a.created',
				'published', 'a.published',
				// Sort-by columns which are only ever addressed with their table prefix, since the
				// join against #__ars_categories makes the bare column names ambiguous.
				'a.category',
				'a.packname',
				'a.access',
				'a.show_unauth_links',
				'a.redirect_unauth',
				'a.created_by',
				'a.modified',
				'a.modified_by',
				'packname',
				'environments', 'a.environments',
				'cat_title',
				'cat_alias',
				'cat_type',
			];
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Get the list items, with the environments column decoded into an array of environment IDs.
	 *
	 * The list query reads the column straight out of the database, where it is stored as JSON (or, on very old
	 * records, as a comma–separated list). Decoding it here means consumers — the JSON:API view in particular —
	 * see an array of IDs instead of having to guess at the storage format.
	 *
	 * @return  mixed
	 * @since   7.5.1
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (!is_array($items))
		{
			return $items;
		}

		foreach ($items as $item)
		{
			if (!is_object($item))
			{
				continue;
			}

			$item->environments = $this->normaliseEnvironments($item->environments ?? null);
		}

		return $items;
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$catid = $app->getUserStateFromRequest($this->context . 'filter.category_id', 'filter_category_id', '', 'string');
		$this->setState('filter.category_id', ($catid === '') ? $catid : (int) $catid);

		$published = $app->getUserStateFromRequest($this->context . 'filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', ($published === '') ? $published : (int) $published);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.title');
		$id .= ':' . $this->getState('filter.packname');
		$id .= ':' . $this->getState('filter.environment_id');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.show_unauth_links');
		$id .= ':' . serialize($this->getState('filter.access'));

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('a') . '.*',
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('c.alias', 'cat_alias'),
				$db->quoteName('c.type', 'cat_type'),
			])
			->from($db->qn('#__ars_autoitemdesc', 'a'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.category'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . $search . '%';
				$query->where(
					'(' .
					$db->qn('a.title') . ' LIKE :search1' . ' OR ' .
					$db->qn('a.description') . ' LIKE :search2' . ' OR ' .
					$db->qn('a.packname') . ' LIKE :search3'
					. ')'
				)
					->bind(':search1', $search)
					->bind(':search2', $search)
					->bind(':search3', $search);
			}
		}

		// Record ID filter
		$recordId = $this->getState('filter.id');

		if (is_numeric($recordId))
		{
			$recordId = (int) $recordId;

			$query->where($db->quoteName('a.id') . ' = :recordId')
				->bind(':recordId', $recordId, ParameterType::INTEGER);
		}

		// Category ID filter
		$catId = $this->getState('filter.category_id');

		if (is_numeric($catId))
		{
			$query->where($db->quoteName('a.category') . ' = :catid')
				->bind(':catid', $catId, ParameterType::INTEGER);
		}

		// Title filter (partial match)
		$title = $this->getState('filter.title');

		if (!empty($title))
		{
			$title = '%' . $title . '%';

			$query->where($db->quoteName('a.title') . ' LIKE :title')
				->bind(':title', $title, ParameterType::STRING);
		}

		// Pack name filter (partial match against the fnmatch pattern itself, NOT an fnmatch evaluation)
		$packname = $this->getState('filter.packname');

		if (!empty($packname))
		{
			$packname = '%' . $packname . '%';

			$query->where($db->quoteName('a.packname') . ' LIKE :packname')
				->bind(':packname', $packname, ParameterType::STRING);
		}

		// Environment ID filter
		$environmentId = $this->getState('filter.environment_id');

		if (is_numeric($environmentId) && $environmentId > 0)
		{
			$this->applyEnvironmentFilter($query, 'a.environments', (int) $environmentId);
		}

		// Created by (user ID) filter
		$createdBy = $this->getState('filter.created_by');

		if (is_numeric($createdBy))
		{
			$createdBy = (int) $createdBy;

			$query->where($db->quoteName('a.created_by') . ' = :createdBy')
				->bind(':createdBy', $createdBy, ParameterType::INTEGER);
		}

		// Access level filter. Accepts either a single access level or a list of them.
		$access = $this->getState('filter.access');

		if (is_numeric($access))
		{
			$access = (int) $access;

			$query->where($db->quoteName('a.access') . ' = :access')
				->bind(':access', $access, ParameterType::INTEGER);
		}
		elseif (is_array($access) && !empty($access))
		{
			$access = ArrayHelper::toInteger($access);

			$query->whereIn($db->quoteName('a.access'), $access);
		}

		// Show unauthorised links filter
		$showUnauthLinks = $this->getState('filter.show_unauth_links');

		if (is_numeric($showUnauthLinks))
		{
			$showUnauthLinks = (int) $showUnauthLinks;

			$query->where($db->quoteName('a.show_unauth_links') . ' = :showUnauthLinks')
				->bind(':showUnauthLinks', $showUnauthLinks, ParameterType::INTEGER);
		}

		// Published filter
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = strtoupper($this->state->get('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
		$ordering  = $db->quoteName($orderCol) . ' ' . $orderDirn;

		$query->order($ordering);

		return $query;
	}
}
