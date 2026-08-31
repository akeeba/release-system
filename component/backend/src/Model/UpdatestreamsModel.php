<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\DbQuery;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class UpdatestreamsModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			// Note: misleadingly named, the filter_fields must also include the sort by fields.
			$config['filter_fields'] = [
				'search',
				'id', 'a.id',
				'category_id', 'c.id',
				'name', 'a.name',
				'created', 'a.created',
				'published', 'a.published',
				// Sort-by columns which are only ever addressed with their table prefix, since the
				// join against #__ars_categories makes the bare column names ambiguous.
				'a.alias',
				'a.type',
				'a.element',
				'a.category',
				'a.packname',
				'a.client_id',
				'a.folder',
				'a.created_by',
				'a.modified',
				'a.modified_by',
				'element',
				'packname',
				'client_id',
				'folder',
				'cat_title',
				'cat_alias',
				'cat_type',
			];
		}

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.id', $direction = 'asc')
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
		$id .= ':' . $this->getState('filter.name');
		$id .= ':' . $this->getState('filter.alias');
		$id .= ':' . $this->getState('filter.type');
		$id .= ':' . $this->getState('filter.element');
		$id .= ':' . $this->getState('filter.folder');
		$id .= ':' . $this->getState('filter.packname');
		$id .= ':' . $this->getState('filter.client_id');
		$id .= ':' . $this->getState('filter.created_by');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
			->select([
				$db->quoteName('a') . '.*',
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('c.alias', 'cat_alias'),
				$db->quoteName('c.type', 'cat_type'),
			])
			->from($db->qn('#__ars_updatestreams', 'a'))
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
					$db->qn('a.name') . ' LIKE :search1' . ' OR ' .
					$db->qn('a.alias') . ' LIKE :search2' . ' OR ' .
					$db->qn('a.element') . ' LIKE :search3'
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

		// Name filter (partial match)
		$name = $this->getState('filter.name');

		if (!empty($name))
		{
			$name = '%' . $name . '%';

			$query->where($db->quoteName('a.name') . ' LIKE :name')
				->bind(':name', $name, ParameterType::STRING);
		}

		// Alias filter (exact match)
		$alias = $this->getState('filter.alias');

		if (!empty($alias))
		{
			$query->where($db->quoteName('a.alias') . ' = :alias')
				->bind(':alias', $alias, ParameterType::STRING);
		}

		// Extension type filter, e.g. `components`, `packages`, `plugins`, ...
		$type = $this->getState('filter.type');

		if (!empty($type) && in_array($type, ['components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates'], true))
		{
			$query->where($db->quoteName('a.type') . ' = :type')
				->bind(':type', $type, ParameterType::STRING);
		}

		// Extension element (short name) filter, exact match, e.g. `com_ars` or `pkg_ars`.
		$element = $this->getState('filter.element');

		if (!empty($element))
		{
			$query->where($db->quoteName('a.element') . ' = :element')
				->bind(':element', $element, ParameterType::STRING);
		}

		// Plugin folder filter, exact match, e.g. `system`.
		$folder = $this->getState('filter.folder');

		if (!empty($folder))
		{
			$query->where($db->quoteName('a.folder') . ' = :folder')
				->bind(':folder', $folder, ParameterType::STRING);
		}

		// Pack name filter (partial match against the fnmatch pattern itself, NOT an fnmatch evaluation)
		$packname = $this->getState('filter.packname');

		if (!empty($packname))
		{
			$packname = '%' . $packname . '%';

			$query->where($db->quoteName('a.packname') . ' LIKE :packname')
				->bind(':packname', $packname, ParameterType::STRING);
		}

		// Joomla application client ID filter: 0 for the site, 1 for the administrator.
		$clientId = $this->getState('filter.client_id');

		if (is_numeric($clientId))
		{
			$clientId = (int) $clientId;

			$query->where($db->quoteName('a.client_id') . ' = :clientId')
				->bind(':clientId', $clientId, ParameterType::INTEGER);
		}

		// Created by (user ID) filter
		$createdBy = $this->getState('filter.created_by');

		if (is_numeric($createdBy))
		{
			$createdBy = (int) $createdBy;

			$query->where($db->quoteName('a.created_by') . ' = :createdBy')
				->bind(':createdBy', $createdBy, ParameterType::INTEGER);
		}

		// Category ID filter
		$catId = $this->getState('filter.category_id');

		if (is_numeric($catId))
		{
			$query->where($db->quoteName('a.category') . ' = :catid')
				->bind(':catid', $catId, ParameterType::INTEGER);
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