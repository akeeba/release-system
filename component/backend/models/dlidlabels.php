<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelDlidlabels extends F0FModel
{
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);
		$db = JFactory::getDbo();

		$fltUsername = $this->getState('username', null, 'string');

		if ($fltUsername)
		{
			$fltUsername = '%' . $fltUsername . '%';
			$q = $db->getQuery(true)
					->select(array(
						$db->qn('id')
					))->from($db->qn('#__users'))
					->where($db->qn('username') . ' LIKE ' . $db->q($fltUsername), 'OR')
					->where($db->qn('name') . ' LIKE ' . $db->q($fltUsername))
					->where($db->qn('email') . ' LIKE ' . $db->q($fltUsername));
			$db->setQuery($q);
			$ids = $db->loadColumn();
			if (!empty($ids))
			{
				$query->where($db->qn('user_id') . 'IN (' . implode(',', $ids) . ')');
			}
			else
			{
				$query->where($db->qn('user_id') . '=' . $db->q(0));
			}
		}

		$fltPrimary = $this->getState('primary', null, 'cmd');

		if (is_numeric($fltPrimary))
		{
			$query->where($db->qn('primary') . ' = ' . $db->q($fltPrimary));
		}

		$query->order($db->qn('primary') . ' DESC');

		return $query;
	}

	protected function onBeforeDelete(&$id, &$table)
	{
		$result = parent::onBeforeDelete($id, $table);

		if ($result)
		{
			// You cannot delete a primary Download ID
			if ($table->primary)
			{
				return false;
			}
		}

		return $result;
	}

	protected function onAfterDelete($id)
	{
		$result = parent::onAfterDelete($id);

		if($result !== false)
		{
			// After adding/deleting a Download ID I have to clear the cache, otherwise I won't see the changes
			F0FUtilsCacheCleaner::clearCacheGroups(array('com_ars'));
		}

		return $result;
	}

	protected function onAfterSave(&$table)
	{
		$result = parent::onAfterSave($table);

		if($result !== false)
		{
			// After adding/deleting a Download ID I have to clear the cache, otherwise I won't see the changes
			F0FUtilsCacheCleaner::clearCacheGroups(array('com_ars'));
		}

		return $result;
	}

	public function resetDownloadId()
	{
		if (is_array($this->id_list) && !empty($this->id_list))
		{
			$table = $this->getTable($this->table);

			foreach ($this->id_list as $id)
			{
				$table->load($id);
				$table->dlid = null;

				if (!$table->check())
				{
					$this->setError($table->getError());

					return false;
				}

				if (!$table->store())
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}
}