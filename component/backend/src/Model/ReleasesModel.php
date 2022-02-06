<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Akeeba\Component\ARS\Site\Model\ItemsModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class ReleasesModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id',
				'r.id',
				'version',
				'r.version',
				'search',
				'category_id',
				'r.category_id',
				'maturity',
				'created',
				'r.created',
				'access',
				'r.access',
				'show_unauth_links',
				'published',
				'language',
				'r.language',
				'ordering',
				'r.ordering',
			];
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Get the absolute filesystem path of the files directory for a specific release ID.
	 *
	 * @param   int  $release_id  The release ID to get the directory for
	 *
	 * @return  string|null  The directory or NULL if it's not defined or does not exist
	 * @throws  Exception
	 * @see     Items::getFilesOptions
	 */
	public function directoryForRelease(int $release_id): ?string
	{
		if (empty($release_id))
		{
			return null;
		}

		/** @var ReleaseTable $releaseModel */
		$release = $this->getTable('Release', 'Administrator');

		// Get the release
		if (!$release->load((int) $release_id))
		{
			return null;
		}

		/** @var CategoryTable $category */
		$category = $this->getTable('Category', 'Administrator');

		if (!$category->load($release->category_id))
		{
			return null;
		}

		// Get which directory to use
		$directory = JPATH_ROOT . '/' . $category->directory;

		if (!Folder::exists($directory))
		{
			return null;
		}

		return $directory;
	}


	/**
	 * Returns the ARS Categories for batch copy/move operations
	 *
	 * @return  array
	 */
	public function getCategories(): array
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('id', 'value'),
				$db->quoteName('title', 'text'),
			])->from($db->quoteName('#__ars_categories'));

		try
		{
			return $db->setQuery($query)->loadAssocList() ?: [];
		}
		catch (Exception $e)
		{
			return [];
		}
	}

	public function getEnvironments(object $release): array
	{
		$ret = [];

		/** @var ItemsModel $itemsModel */
		$itemsModel = $this->getMVCFactory()->createModel('Items', 'Administrator', ['ignore_request' => true]);
		$itemsModel->setState('filter.release_id', $release->id);
		$items = $itemsModel->getItems();

		foreach ($items as $item)
		{
			$environments = $item->environments;

			if (!empty($environments) && is_string($environments))
			{
				$test         = @json_decode($environments);
				$environments = $test ?? array_map('intval', explode(',', $environments));
			}

			if (empty($environments) || !is_array($environments))
			{
				continue;
			}

			$ret = array_merge($ret, $environments);
		}

		if (empty($ret))
		{
			return $ret;
		}

		$ret = array_unique($ret);

		$db     = $this->getDbo();
		$query  = $db->getQuery(true)
			->select($db->quoteName('title'))
			->from($db->quoteName('#__ars_environments'))
			->whereIn($db->quoteName('id'), $ret);
		$titles = $db->setQuery($query)->loadColumn();

		uasort($titles, function ($a, $b) {
			$partsA = explode(' ', $a, 2);
			$partsB = explode(' ', $b, 2);

			if (($partsA[0] != $partsB[0]) || (count($partsA) < 2) || (count($partsB) < 2))
			{
				return $a <=> $b;
			}

			return version_compare($partsA[1], $partsB[1]);
		});

		return $titles;
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('r') . '.*',
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('c.alias', 'cat_alias'),
				$db->quoteName('c.type', 'cat_type'),
				$db->quoteName('l.title', 'language_title'),
				$db->quoteName('l.image', 'language_image'),
				$db->quoteName('ag.title', 'access_level'),
			])
			->from($db->qn('#__ars_releases', 'r'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('r.access'))
			->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('r.language'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('r.id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			elseif (stripos($search, 'version:') === 0)
			{
				$version = substr($search, 8);
				$query->where($db->quoteName('r.version') . ' = :version')
					->bind(':version', $version, ParameterType::STRING);
			}
			else
			{
				$search = '%' . $search . '%';
				$query->where(
					'(' .
					$db->qn('r.version') . ' LIKE :search1' . ' OR ' .
					$db->qn('r.notes') . ' LIKE :search2'
					. ')'
				)
					->bind(':search1', $search)
					->bind(':search2', $search);
			}
		}

		// Category ID filter
		$catId = $this->getState('filter.category_id');

		if (is_numeric($catId))
		{
			$query->where($db->quoteName('r.category_id') . ' = :catid')
				->bind(':catid', $catId, ParameterType::INTEGER);
		}

		// Published filter
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('r.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// Maturity filter
		$maturity = $this->getState('filter.maturity');

		if (!empty($maturity))
		{
			$query->where($db->quoteName('r.maturity') . ' = :maturity')
				->bind(':maturity', $maturity);
		}

		// Show unauthorised links filter
		$showUnauthLinks = $this->getState('filter.show_unauth_links');

		if (is_numeric($showUnauthLinks))
		{
			$query->where($db->quoteName('r.show_unauth_links') . ' = :show_unauth_links')
				->bind(':show_unauth_links', $showUnauthLinks, ParameterType::INTEGER);
		}

		// Access filter
		$access      = $this->getState('filter.access');
		$allowUnauth = $this->getState('filter.allowUnauth', 0) == 1;

		if (is_numeric($access))
		{
			if ($allowUnauth)
			{
				$query->extendWhere('AND', [
					$db->quoteName('r.access') . ' = :access',
					$db->quoteName('r.show_unauth_links') . ' = ' . $db->quote(1),
				], 'OR')
					->bind(':access', $access, ParameterType::INTEGER);
			}
			else
			{
				$query->where($db->quoteName('r.access') . ' = :access')
					->bind(':access', $access, ParameterType::INTEGER);
			}
		}
		elseif (is_array($access))
		{
			$access = ArrayHelper::toInteger($access);

			if ($allowUnauth)
			{
				$query->extendWhere('AND', [
					$db->quoteName('r.access') . ' IN(' . implode(',', $query->bindArray($access, ParameterType::INTEGER)) . ')',
					$db->quoteName('r.show_unauth_links') . ' = ' . $db->quote(1),
				], 'OR');
			}
			else
			{
				$query->whereIn($db->quoteName('r.access'), $access);
			}
		}

		// Language filter
		$language = $this->getState('filter.language');

		if (!empty($language))
		{
			if (is_scalar($language))
			{
				$query->where($db->quoteName('r.language') . ' = :language')
					->bind(':language', $language);
			}
			else
			{
				$query->whereIn($db->quoteName('r.language'), $language, ParameterType::STRING);
			}
		}

		// Latest releases filter
		$latest = $this->getState('filter.latest', false);

		if ($latest === true)
		{
			$latestIDsQuery = $db->getQuery(true)
				->select($db->qn('r1.id'))
				->from($db->qn('#__ars_releases') . ' AS ' . $db->qn('r1'))
				->leftJoin($db->qn('#__ars_releases') . ' AS ' . $db->qn('r2') . ' ON (' .
					$db->qn('r1.category_id') . ' = ' . $db->qn('r2.category_id') . ' AND ' .
					$db->qn('r1.ordering') . ' > ' . $db->qn('r2.ordering') .
					($published !== '' ? (' AND ' . $db->qn('r2.published') . ' = ' . $db->q($published)) : '')
					. ')')
				->where($db->qn('r2.ordering') . ' IS NULL');

			$query->where($db->quoteName('r.id') . ' IN(' . $latestIDsQuery . ')');
		}

		// Minimum maturity filter
		$minMaturity = $this->getState('filter.minMaturity', 'alpha');

		switch ($minMaturity)
		{
			case 'beta':
				$maturityItems = ['beta', 'rc', 'stable'];
				$query->whereIn($db->quoteName('maturity'), $maturityItems, ParameterType::STRING);
				break;
			case 'rc':
				$maturityItems = ['rc', 'stable'];
				$query->whereIn($db->quoteName('maturity'), $maturityItems, ParameterType::STRING);
				break;
			case 'stable':
				$query->where($db->quoteName('maturity') . ' = ' . $db->quote('stable'));
				break;
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'r.ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.maturity');
		$id .= ':' . $this->getState('filter.show_unauth_links');
		$id .= ':' . serialize($this->getState('filter.language'));
		$id .= ':' . $this->getState('filter.minMaturity');
		$id .= ':' . ($this->getState('filter.latest') ? 'latest' : '');
		$id .= ':' . serialize($this->getState('filter.access'));

		return parent::getStoreId($id);
	}

	protected function populateState($ordering = 'r.id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$catid = $app->getUserStateFromRequest($this->context . 'filter.category_id', 'filter_category_id', '', 'string');
		$this->setState('filter.category_id', ($catid === '') ? $catid : (int) $catid);

		$published = $app->getUserStateFromRequest($this->context . 'filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', ($published === '') ? $published : (int) $published);

		$maturity = $app->getUserStateFromRequest($this->context . 'filter.maturity', 'filter_maturity', '', 'string');
		$this->setState('filter.maturity', $maturity);

		$showUnauthLinks = $app->getUserStateFromRequest($this->context . 'filter.show_unauth_links', 'filter_show_unauth_links', '', 'string');
		$this->setState('filter.show_unauth_links', ($showUnauthLinks === '') ? $showUnauthLinks : (int) $showUnauthLinks);

		$access = $app->getUserStateFromRequest($this->context . 'filter.access', 'filter_access', '', 'string');
		$access = is_array($access) ? ArrayHelper::toInteger($access) : (
		($access === '') ? $access : (int) $access
		);
		$this->setState('filter.access', $access);

		$language = $app->getUserStateFromRequest($this->context . 'filter.language', 'filter_language', '', 'string');
		$this->setState('filter.language', $language);

		$this->setState('filter.allowUnauth', 0);
		$this->setState('filter.minMaturity', 'alpha');
		$this->setState('filter.latest', false);

		parent::populateState($ordering, $direction);
	}
}
