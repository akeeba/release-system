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
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

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

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.dlid');
		$id .= ':' . $this->getState('filter.published');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('i') . '.*',
				$db->quoteName('u.name'),
				$db->quoteName('u.username'),
				$db->quoteName('u.email'),
			])
			->from($db->qn('#__ars_dlidlabels', 'i'))
			->join('LEFT', $db->qn('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('i.user_id'));

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
					$db->qn('u.name') . ' LIKE :search1' . ' OR ' .
					$db->qn('u.username') . ' LIKE :search2'
					. ')'
				)
					->bind(':search1', $search)
					->bind(':search2', $search);
			}
		}

		// Download ID filter
		$dlid   = $this->getState('filter.dlid');
		$userId = null;

		if (strpos($dlid, ':') != false)
		{
			[$userId, $dlid] = explode(':', $dlid);
		}

		if (!empty($dlid))
		{
			$dlid = '%' . $dlid . '%';
			$query->where($db->quoteName('i.dlid') . ' LIKE :dlid')
				->bind(':dlid', $dlid, ParameterType::STRING);
		}

		if (is_numeric($userId) && ($userId > 0))
		{
			$userId = (int) $userId;

			$query->where($db->quoteName('i.user_id') . ' = :userid')
				->bind(':userid', $userId, ParameterType::INTEGER);
		}

		// Published filter
		$published = $this->getState('filter.published');

		if (is_numeric($published))
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