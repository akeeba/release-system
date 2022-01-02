<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

class CategoriesModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'title',
				'id',
				'type',
				'created',
				'access',
				'show_unauth_links',
				'published',
				'is_supported',
				'language',
				'ordering',
			];
		}

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'ordering', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . 'filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', ($published === '') ? $published : (int) $published);

		$showUnauthLinks = $app->getUserStateFromRequest($this->context . 'filter.filter_show_unauth_links', 'filter_show_unauth_links', '', 'string');
		$this->setState('filter.show_unauth_links', ($showUnauthLinks === '') ? $showUnauthLinks : (int) $showUnauthLinks);

		$supported = $app->getUserStateFromRequest($this->context . 'filter.supported', 'filter_supported', '', 'string');
		$this->setState('filter.supported', ($supported === '') ? $supported : (int) $supported);

		$access = $app->getUserStateFromRequest($this->context . 'filter.access', 'filter_access', '', 'string');
		$this->setState('filter.access', ($access === '') ? $access : (int) $access);

		$language = $app->getUserStateFromRequest($this->context . 'filter.language', 'filter_language', '', 'string');
		$this->setState('filter.language', $language);

		$this->setState('filter.allowUnauth', 0);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id    .= ':' . $this->getState('filter.search');
		$id    .= ':' . $this->getState('filter.published');
		$id    .= ':' . $this->getState('filter.show_unauth_links');
		$id    .= ':' . $this->getState('filter.supported');
		$id    .= ':' . serialize($this->getState('filter.language'));
		$id    .= ':' . $this->getState('filter.allowUnauth');
		$id    .= ':' . serialize($this->getState('filter.access'));

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('c') . '.*',
				$db->quoteName('l.title', 'language_title'),
				$db->quoteName('l.image', 'language_image'),
				$db->quoteName('ag.title', 'access_level'),
			])
			->from($db->qn('#__ars_categories', 'c'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('c.access'))
			->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('c.language'));

		// Search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('c.id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . $search . '%';
				$query->where(
					'(' .
					$db->qn('c.title') . ' LIKE :search1' . ' OR ' .
					$db->qn('c.description') . ' LIKE :search2'
					. ')'
				)
					->bind(':search1', $search)
					->bind(':search2', $search);
			}
		}

		// Published filter
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('c.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// Show unauthorised links filter
		$showUnauthLinks = $this->getState('filter.show_unauth_links');

		if (is_numeric($showUnauthLinks))
		{
			$query->where($db->quoteName('c.show_unauth_links') . ' = :show_unauth_links')
				->bind(':show_unauth_links', $showUnauthLinks, ParameterType::INTEGER);
		}

		// Supported filter
		$supported = $this->getState('filter.supported');

		if (is_numeric($supported))
		{
			$query->where($db->quoteName('c.is_supported') . ' = :supported')
				->bind(':supported', $supported, ParameterType::INTEGER);
		}

		// Access filter
		$access      = $this->getState('filter.access');
		$allowUnauth = $this->getState('filter.allowUnauth', 0) == 1;

		if (is_numeric($access))
		{
			if ($allowUnauth)
			{
				$query->extendWhere('AND', [
					$db->quoteName('c.access') . ' = :access',
					$db->quoteName('c.show_unauth_links') . ' = ' . $db->quote(1),
				], 'OR')
					->bind(':access', $access, ParameterType::INTEGER);
			}
			else
			{
				$query->where($db->quoteName('c.access') . ' = :access')
					->bind(':access', $access, ParameterType::INTEGER);
			}
		}
		elseif (is_array($access))
		{
			$access = ArrayHelper::toInteger($access);

			if ($allowUnauth)
			{
				$query->extendWhere('AND', [
					$db->quoteName('c.access') . ' IN(' . implode(',', $query->bindArray($access, ParameterType::INTEGER)) . ')',
					$db->quoteName('c.show_unauth_links') . ' = ' . $db->quote(1),
				], 'OR');
			}
			else
			{
				$query->whereIn($db->quoteName('c.access'), $access);
			}
		}

		// Language filter
		$language = $this->getState('filter.language');

		if (!empty($language))
		{
			if (is_scalar($language))
			{
				$query->where($db->quoteName('c.language') . ' = :language')
					->bind(':language', $language);
			}
			else
			{
				$query->whereIn($db->quoteName('c.language'), $language, ParameterType::STRING);
			}
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}