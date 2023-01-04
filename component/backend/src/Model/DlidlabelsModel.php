<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class DlidlabelsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'id', 'i.id',
				'user_id', 'i.user_id',
				'dlid', 'i.dlid',
				'primary', 'i.primary',
				'created', 'i.created',
				'published', 'i.published',
			];
		}

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'i.id', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$dlid = $app->getUserStateFromRequest($this->context . 'filter.dlid', 'filter_dlid', '', 'string');
		$this->setState('filter.dlid', $dlid);

		$published = $app->getUserStateFromRequest($this->context . 'filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', ($published === '') ? $published : (int) $published);

		$this->setState('filter.user_id', null);

		// Special considerations for frontend
		if ($app->isClient('site'))
		{
			// Only show the user's own Download IDs
			$this->setState('filter.user_id', $app->getIdentity()->id);
		}

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$app = Factory::getApplication();

		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.dlid');
		$id .= ':' . $this->getState('filter.published');

		// Special considerations for frontend
		if ($app->isClient('site'))
		{
			$id .= ':' . ($app->getIdentity()->id ?? 0);
		}

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$app    = Factory::getApplication();
		$isSite = $app->isClient('site');

		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('i') . '.*',
				$db->quoteName('u.name'),
				$db->quoteName('u.username'),
				$db->quoteName('u.email'),
			])
			->from($db->qn('#__ars_dlidlabels', 'i'))
			->join('LEFT', $db->qn('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('i.user_id'));

		// Get the filter states up first
		$search    = $this->getState('filter.search');
		$dlid      = $this->getState('filter.dlid');
		$published = $this->getState('filter.published');
		$userId    = null;

		// In the frontend we only allow a search filter.
		if ($isSite)
		{
			$userId    = $app->getIdentity()->id;
			$published = '';
			$dlid      = null;
		}
		// In the backend we can search for any Download ID which can include a user ID part
		else
		{
			if (strpos($dlid, ':') != false)
			{
				[$userId, $dlid] = explode(':', $dlid);
			}
		}

		// User filter. DO NOT MOVE.
		if (is_numeric($userId) && ($userId > 0))
		{
			$userId = (int) $userId;

			$query->where($db->quoteName('i.user_id') . ' = :userid')
				->bind(':userid', $userId, ParameterType::INTEGER);
		}

		// Search filter
		if (!empty($search))
		{
			// In the backend we can for any user's Download ID by numeric ID or partial username / name match.
			if (!$isSite)
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
						$db->qn('u.name') . ' LIKE :search1' . ' OR ' .
						$db->qn('u.username') . ' LIKE :search2'
						. ')'
					)
						->bind(':search1', $search)
						->bind(':search2', $search);
				}
			}
			// In the frontend we search for the title or the download ID itself
			else
			{
				$dlid = $search;

				/**
				 * If the search filter has a colon it might be a Download ID. The first part would be a user ID, the
				 * second part the dlid in the table. If that first part is numeric but does not match our user we do
				 * not treat it as a Download ID at all (prevents returning someone else's records).
				 */
				if (strpos($dlid, ':') != false)
				{
					[$junkId, $dlid] = explode(':', $dlid);

					$dlid = (is_numeric($junkId) && ($junkId != $app->getIdentity()->id)) ? '' : $dlid;
				}

				$search = '%' . $search . '%';
				$dlid = empty($dlid) ? $dlid : ('%' . $dlid . '%');

				if (!empty($dlid))
				{
					// Search for any record matching either the title OR the dlid
					$query->extendWhere('AND', [
						$db->quoteName('title') . 'LIKE :search',
						$db->quoteName('dlid') . ' LIKE :dlid',
					], 'OR')
						->bind(':search', $search, ParameterType::STRING)
						->bind(':dlid', $dlid, ParameterType::STRING);
				}
				else
				{
					$query->where($db->quoteName('title') . ' LIKE :search')
						->bind(':search', $search);
				}
			}
		}

		// Download ID filter
		if (!empty($dlid) && !$isSite)
		{
			$dlid = '%' . $dlid . '%';
			$query->where($db->quoteName('i.dlid') . ' LIKE :dlid')
				->bind(':dlid', $dlid, ParameterType::STRING);
		}

		// Published filter
		if (is_numeric($published) && !$isSite)
		{
			$query->where($db->quoteName('i.published') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// List ordering clause
		$orderCol  = $this->state->get('list.ordering', 'i.id');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		$ordering  = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}
}