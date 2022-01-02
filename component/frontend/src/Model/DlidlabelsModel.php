<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;


use Akeeba\Component\ARS\Administrator\Table\DlidlabelTable;
use Joomla\CMS\Factory;

class DlidlabelsModel extends \Akeeba\Component\ARS\Administrator\Model\DlidlabelsModel
{
	public function myDownloadID($user = null): ?string
	{
		$user = $user ?? Factory::getApplication()->getIdentity();

		if ($user->guest || ($user->id <= 0))
		{
			return '';
		}

		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_dlidlabels'))
			->where($db->quoteName('user_id') . ' = :user_id')
			->where($db->quoteName('primary') . ' = 1')
			->bind(':user_id', $user->id);
		$data = $db->setQuery($query)->loadAssoc() ?: [];

		/** @var DlidlabelTable $table */
		$table = $this->getTable('dlidlabel');

		if (empty($data))
		{
			$table->save([
				'user_id' => $user->id,
				'published' => 1,
				'primary' => 1
			]);

			return $table->dlid;
		}

		return $data['dlid'];
	}

}