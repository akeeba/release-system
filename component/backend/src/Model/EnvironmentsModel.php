<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class EnvironmentsModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			// Note: misleadingly named, the filter_fields must also include the sort by fields.
			$config['filter_fields'] = [
				'search',
				'id', 'a.id',
				'title', 'a.title',
				'xmltitle', 'a.xmltitle',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'modified', 'a.modified',
				'modified_by', 'a.modified_by',
			];
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Returns a mapping of environment IDs to their titles
	 *
	 * @return  array
	 */
	public function getEnvironmentTitles(): array
	{
		$db = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('id'),
				$db->quoteName('title'),
			])
			->from($db->quoteName('#__ars_environments'));

		return $db->setQuery($query)->loadAssocList('id', 'title') ?: [];
	}

	/**
	 * Returns a mapping of environment IDs to their xml titles
	 *
	 * @return  array
	 */
	public function getEnvironmentXMLTitles(): array
	{
		$db = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('id'),
				$db->quoteName('xmltitle'),
			])
			->from($db->quoteName('#__ars_environments'));

		return $db->setQuery($query)->loadAssocList('id', 'xmltitle') ?: [];
	}

	protected function populateState($ordering = 'a.title', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.title');
		$id .= ':' . $this->getState('filter.xmltitle');
		$id .= ':' . $this->getState('filter.platform');
		$id .= ':' . $this->getState('filter.created_by');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('a') . '.*',
			])
			->from($db->qn('#__ars_environments', 'a'));

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
					$db->qn('a.xmltitle') . ' LIKE :search2'
					. ')'
				)
					->bind(':search1', $search)
					->bind(':search2', $search);
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

		// Title filter (partial match)
		$title = $this->getState('filter.title');

		if (!empty($title))
		{
			$title = '%' . $title . '%';

			$query->where($db->quoteName('a.title') . ' LIKE :title')
				->bind(':title', $title, ParameterType::STRING);
		}

		// XML title filter (partial match), e.g. `php/8.3` or just `8.3`.
		$xmlTitle = $this->getState('filter.xmltitle');

		if (!empty($xmlTitle))
		{
			$xmlTitle = '%' . $xmlTitle . '%';

			$query->where($db->quoteName('a.xmltitle') . ' LIKE :xmltitle')
				->bind(':xmltitle', $xmlTitle, ParameterType::STRING);
		}

		/**
		 * Platform filter: the part of the XML title before the slash, e.g. `php`, `joomla`, `wordpress`.
		 *
		 * This is an anchored match, so filtering by `php` returns the PHP version environments without
		 * also returning anything which merely happens to have `php` somewhere in its version part.
		 */
		$platform = $this->getState('filter.platform');

		if (!empty($platform))
		{
			$platform = $platform . '/%';

			$query->where($db->quoteName('a.xmltitle') . ' LIKE :platform')
				->bind(':platform', $platform, ParameterType::STRING);
		}

		// Created by (user ID) filter
		$createdBy = $this->getState('filter.created_by');

		if (is_numeric($createdBy))
		{
			$createdBy = (int) $createdBy;

			$query->where($db->quoteName('a.created_by') . ' = :createdBy')
				->bind(':createdBy', $createdBy, ParameterType::INTEGER);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'a.title');
		$orderDirn = strtoupper($this->state->get('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
		$ordering  = $db->quoteName($orderCol) . ' ' . $orderDirn;

		$query->order($ordering);

		return $query;
	}
}