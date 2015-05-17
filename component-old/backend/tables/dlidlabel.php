<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableDlidlabel extends F0FTable
{
	public function check()
	{
		$result = parent::check();

		if ($result)
		{
			// Force user_id to be the current user ID in the front-end
			list($isCli, $isAdmin) = F0FDispatcher::isCliAdmin();

			if (!$isAdmin && !$isCli)
			{
				$this->user_id = JFactory::getUser()->id;
			}

			$db = $this->getDbo();

			// Should this be a primary or a secondary DLID?
			if (is_null($this->primary))
			{
				// Do I have another primary?
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__ars_dlidlabels'))
					->where($db->qn('user_id') . ' = ' . $db->q($this->user_id))
					->where($db->qn('primary') . ' = ' . $db->q(1));

				if ($this->ars_dlidlabel_id)
				{
					$query->where('NOT(' . $db->qn('ars_dlidlabel_id') . ' = ' . $db->q($this->ars_dlidlabel_id) . ')');
				}

				$hasPrimary = $db->setQuery($query)->loadResult();

				$this->primary = $hasPrimary ? 0 : 1;
			}

			if ($this->primary)
			{
				// You can never disable a primary Download ID
				$this->enabled = 1;
				// The primary Download ID title is fixed
				$this->label = '_MAIN_';
			}

			// Do I need to generate a download ID?
			if (empty($this->dlid))
			{
				while (empty($this->dlid))
				{
					$this->dlid = md5(JCrypt::genRandomBytes(64));

					// Do I have another primary?
					$query = $db->getQuery(true)
								->select('COUNT(*)')
								->from($db->qn('#__ars_dlidlabels'))
								->where($db->qn('dlid') . ' = ' . $db->q($this->dlid))
								->where($db->qn('user_id') . ' = ' . $db->q($this->user_id))
								->where($db->qn('primary') . ' = ' . $db->q($this->primary));

					if ($this->ars_dlidlabel_id)
					{
						$query->where('NOT(' . $db->qn('ars_dlidlabel_id') . ' = ' . $db->q($this->ars_dlidlabel_id) . ')');
					}

					$dlidColission = $db->setQuery($query)->loadResult();

					if ($dlidColission)
					{
						$this->dlid = null;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * The event which runs before a record is (un)published
	 *
	 * @param   integer|array &$cid    The PK IDs of the records being (un)published
	 * @param   integer       $publish 1 to publish, 0 to unpublish
	 *
	 * @return  boolean  True to allow the (un)publish to proceed
	 */
	protected function onBeforePublish(&$cid, $publish)
	{
		$result = parent::onBeforePublish($cid, $publish);

		if ($result)
		{
			// Make sure we are not trying to unpublish the default download ID of a user
			foreach ($cid as $id)
			{
				$temp = clone $this;
				$temp->reset();
				$temp->load($id);

				if ($temp->primary)
				{
					$this->setError(JText::_("COM_ARS_DLIDLABELS_ERR_CANTUNPUBLISHDEFAULT"));
					return false;
				}
			}
		}

		return $result;
	}
}