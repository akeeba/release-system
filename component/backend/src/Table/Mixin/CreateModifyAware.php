<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

trait CreateModifyAware
{
	public function onBeforeStore($updateNulls = false)
	{
		$date = Factory::getDate()->toSql();
		$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();

		// Set created date if not set.
		if ($this->hasField('created_on') && !(int) $this->created_on)
		{
			$this->created_on = $date;
		}

		if ($this->getId())
		{
			// Existing item
			if ($this->hasField('modified_by'))
			{
				$this->modified_by = $user->id;
			}
			if ($this->hasField('modified_on'))
			{
				$this->modified_on = $date;

			}
		}
		else
		{
			// Field created_by can be set by the user, so we don't touch it if it's set.
			if ($this->hasField('created_by') && empty($this->created_by))
			{
				$this->created_by = $user->id;
			}

			// Set modified to created date if not set
			if ($this->hasField('modified_on') && $this->hasField('created_on') && !(int) $this->modified_on)
			{
				$this->modified_on = $this->created_on;
			}

			// Set modified_by to created_by user if not set
			if ($this->hasField('modified_by') && $this->hasField('created_by') && empty($this->modified_by))
			{
				$this->modified_by = $this->created_by;
			}
		}
	}
}