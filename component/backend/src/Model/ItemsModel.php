<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class ItemsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'id', 'i.id',
				'release_id', 'i.release_id',
				'category_id', 'c.id',
				'title', 'i.title',
				'created', 'i.created',
				'access', 'i.access',
				'show_unauth_links', 'i.show_unauth_links',
				'published', 'i.published',
				'language', 'i.language',
				'ordering', 'i.ordering',
			];
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Returns the ARS Releases for batch copy/move operations
	 *
	 * @return  array
	 */
	public function getReleases(): array
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('r.id', 'value'),
				$db->quoteName('r.version', 'text'),
			])
			->from($db->quoteName('#__ars_releases', 'r'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id')
			);

		$catId = $this->getState('filter.category_id');

		if ($catId)
		{
			$query->where($db->quoteName('r.category_id') . ' = :catid')
				->bind(':catid', $catId, ParameterType::INTEGER);
		}

		try
		{
			// Get the flat results and group them. loadAssocList() can't do it by itself with non0unique keys.
			$results = $db->setQuery($query)->loadAssocList() ?: [];

			$temp = [];

			foreach ($results as $result)
			{
				$groupKey          = $result['cat_title'];
				$temp[$groupKey]   = $temp[$groupKey] ?? [];
				$temp[$groupKey][] = [
					'value' => $result['value'],
					'text'  => $result['text'],
				];
			}

			return $temp;
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getReleasesOptions(): array
	{
		return array_merge(
			[
				HTMLHelper::_('select.option', '', Text::_('JLIB_HTML_BATCH_NO_CATEGORY')),
			],
			array_map(function ($records) {
				if (empty($records))
				{
					return [];
				}

				return array_map(function (array $record) {
					return HTMLHelper::_('select.option', $record['value'], $record['text']);
				}, $records);
			}, $this->getReleases())
		);
	}

	public function getCategoryFromRelease(int $releaseId)
	{
		/** @var ReleaseTable $release */
		$release = $this->getMVCFactory()->createTable('Release', 'Administrator');

		if (!$release->load($releaseId))
		{
			return null;
		}

		return $release->category_id;
	}

	protected function populateState($ordering = 'r.id', $direction = 'desc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$catid = $app->getUserStateFromRequest($this->context . 'filter.category_id', 'filter_category_id', '', 'string');
		$this->setState('filter.category_id', ($catid === '') ? $catid : (int) $catid);

		$releaseId = $app->getUserStateFromRequest($this->context . 'filter.release_id', 'filter_release_id', '', 'string');
		$this->setState('filter.release_id', ($releaseId === '') ? $releaseId : (int) $releaseId);

		$published = $app->getUserStateFromRequest($this->context . 'filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', ($published === '') ? $published : (int) $published);

		$showUnauthLinks = $app->getUserStateFromRequest($this->context . 'filter.filter_show_unauth_links', 'filter_show_unauth_links', '', 'string');
		$this->setState('filter.show_unauth_links', ($showUnauthLinks === '') ? $showUnauthLinks : (int) $showUnauthLinks);

		$access = $app->getUserStateFromRequest($this->context . 'filter.access', 'filter_access', '', 'string');
		$this->setState('filter.access', ($access === '') ? $access : (int) $access);

		$language = $app->getUserStateFromRequest($this->context . 'filter.language', 'filter_language', '', 'string');
		$this->setState('filter.language', $language);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.release_id');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.show_unauth_links');
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . serialize($this->getState('filter.access'));

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('i') . '.*',
				$db->quoteName('r.version', 'version'),
				$db->quoteName('r.alias', 'rel_alias'),
				$db->quoteName('r.category_id', 'cat_id'),
				$db->quoteName('c.title', 'cat_title'),
				$db->quoteName('c.alias', 'cat_alias'),
				$db->quoteName('c.type', 'cat_type'),
				$db->quoteName('l.title', 'language_title'),
				$db->quoteName('l.image', 'language_image'),
				$db->quoteName('ag.title', 'access_level'),
			])
			->from($db->qn('#__ars_items', 'i'))
			->join('LEFT', $db->qn('#__ars_releases', 'r'), $db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id'))
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('i.access'))
			->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('i.language'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('i.id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . $search . '%';
				$query->where(
					'(' .
					$db->qn('i.title') . ' LIKE :search1' . ' OR ' .
					$db->qn('i.description') . ' LIKE :search2'
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

		// Release ID filter
		$releaseId = $this->getState('filter.release_id');

		if (is_numeric($releaseId))
		{
			$query->where($db->quoteName('i.release_id') . ' = :relid')
				->bind(':relid', $releaseId, ParameterType::INTEGER);
		}

		// Published filter
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('i.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// Show unauthorised links filter
		$showUnauthLinks = $this->getState('filter.show_unauth_links');

		if (is_numeric($showUnauthLinks))
		{
			$query->where($db->quoteName('i.show_unauth_links') . ' = :show_unauth_links')
				->bind(':show_unauth_links', $showUnauthLinks, ParameterType::INTEGER);
		}

		// Access filter
		$access = $this->getState('filter.access');

		if (is_numeric($access))
		{
			$query->where($db->quoteName('i.access') . ' = :access')
				->bind(':access', $access, ParameterType::INTEGER);
		}
		elseif (is_array($access))
		{
			$access = ArrayHelper::toInteger($access);
			$query->whereIn($db->quoteName('i.access'), $access);
		}

		// Language filter
		$language = $this->getState('filter.language');

		if (!empty($language))
		{
			$query->where($db->quoteName('i.language') . ' = :language')
				->bind(':language', $language);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'i.ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}