<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelDlidlabels extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);

		$fltUsername		= $this->getState('username', null, 'string');
		if($fltUsername) {
			$db = JFactory::getDbo();
			$fltUsername = '%' . $fltUsername . '%';
			$q = $db->getQuery(true)
				->select(array(
					$db->qn('id')
				))->from($db->qn('#__users'))
				->where($db->qn('username') . ' LIKE ' . $db->q($fltUsername), 'OR')
				->where($db->qn('name') . ' LIKE ' . $db->q($fltUsername))
				->where($db->qn('email') . ' LIKE ' . $db->q($fltUsername))
			;
			$db->setQuery($q);
			$ids = $db->loadColumn();
			if(!empty($ids)) {
				$query->where($db->qn('user_id') . 'IN (' . implode(',', $ids) . ')');
			} else {
				$query->where($db->qn('user_id') . '=' . $db->q(0));
			}
		}

		return $query;
	}
}