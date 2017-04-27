<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\Mixin;

use FOF30\Date\Date;
use FOF30\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Trait for date manipulations commonly used in models
 */
trait DateManipulation
{
	/**
	 * Normalise a date into SQL format
	 *
	 * @param   string $value   The date to normalise
	 * @param   string $default The default date to use if the normalised date is invalid or empty (use 'now' for
	 *                          current date/time)
	 *
	 * @return  string
	 */
	protected function normaliseDate($value, $default = '2001-01-01')
	{
		\JLoader::import('joomla.utilities.date');

		$db = $this->container->db;

		if (empty($value) || ($value == $db->getNullDate()))
		{
			$value = $default;
		}

		if (empty($value) || ($value == $db->getNullDate()))
		{
			return $value;
		}

		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		if (!preg_match($regex, $value))
		{
			$value = $default;
		}

		if (empty($value) || ($value == $db->getNullDate()))
		{
			return $value;
		}

		$date = new Date($value);

		$value = $date->toSql();

		return $value;
	}

	/**
	 * Sort the published up/down times in case they are give out of order. If publish_up equals publish_down the
	 * foreverDate will be used for publish_down.
	 *
	 * @param   string $publish_up   Publish Up date
	 * @param   string $publish_down Publish Down date
	 * @param   string $foreverDate  See above
	 *
	 * @return  array  (publish_up, publish_down)
	 */
	protected function sortPublishDates($publish_up, $publish_down, $foreverDate = '2038-01-18 00:00:00')
	{
		\JLoader::import('joomla.utilities.date');

		$jUp   = new Date($publish_up);
		$jDown = new Date($publish_down);

		if ($jDown->toUnix() < $jUp->toUnix())
		{
			$temp         = $publish_up;
			$publish_up   = $publish_down;
			$publish_down = $temp;
		}
		elseif ($jDown->toUnix() == $jUp->toUnix())
		{
			$jDown        = new Date($foreverDate);
			$publish_down = $jDown->toSql();
		}

		return array($publish_up, $publish_down);
	}

	/**
	 * Publish or unpublish a DataModel item based on its publish_up / publish_down fields
	 *
	 * @param   DataModel  $row  The DataModel to publish/unpublish
	 *
	 * @return  void
	 */
	protected function publishByDate(DataModel $row)
	{
		static $uNow = null;

		\JLoader::import('joomla.utilities.date');

		if (is_null($uNow))
		{
			$jNow = new Date();
			$uNow = $jNow->toUnix();
		}

		$db = $this->container->db;

		$triggered = false;

		if ($row->publish_down && ($row->publish_down != $db->getNullDate()))
		{
			$publish_down = $this->normaliseDate($row->publish_down, '2038-01-18 00:00:00');
			$publish_up   = $this->normaliseDate($row->publish_up, '2001-01-01 00:00:00');

			$jDown = new Date($publish_down);
			$jUp   = new Date($publish_up);

			if (($uNow >= $jDown->toUnix()) && $row->enabled)
			{
				$row->enabled = 0;
				$triggered    = true;
			}
			elseif (($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix()))
			{
				$row->enabled = 1;
				$triggered    = true;
			}
		}

		if ($triggered)
		{
			$row->save();
		}
	}
}