<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Helper\ComponentParams;
use DateInterval;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class ControlpanelModel extends BaseDatabaseModel
{
	/**
	 * Checks if there is at least one menu entry that shows all the categories.
	 * This is needed because otherwise JRoute won't find any suitable menu
	 *
	 * @return bool
	 */
	public function needsCategoriesMenu(): bool
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from('#__menu')
			->where($db->qn('link') . ' = ' . $db->q('index.php?option=com_ars&view=categories&layout=repository'))
			->where($db->qn('published') . ' = ' . $db->q(1));

		return ($db->setQuery($query)->loadResult() ?: 0) >= 1;
	}

	/**
	 * Save some magic variables we need
	 *
	 * @return  void
	 */
	public function saveMagicVariables(): void
	{
		$params = ComponentHelper::getParams($this->option);
		$dirty  = false;

		// Store the URL to this site?
		$siteUrl = Uri::root(false);

		if ($siteUrl !== $params->get('siteurl'))
		{
			$params->set('siteurl', $siteUrl);
			$dirty = true;
		}

		// If any change was made save the component parameters back to the database
		if ($dirty)
		{
			ComponentParams::save($params);
		}
	}

	/**
	 * Returns the total number of (authorized) downloads within a specific time period.
	 *
	 * @param   string  $interval  The time interval to use: alltime, year, lastmonth, month, week, day
	 *
	 * @return  int
	 */
	public function getNumDownloads(string $interval): int
	{
		$db = $this->getDatabase();

		$interval = strtolower($interval);
		$allTime  = false;
		$date     = '';

		switch ($interval)
		{
			case 'alltime':
			default:
				$allTime = true;
				break;

			case 'year':
				$year_start = clone Factory::getDate(date('Y-01-01'));
				$year_end   = clone Factory::getDate(date('Y-12-31'));

				$date = $db->q($year_start->toSql()) . " AND " . $db->q($year_end->toSql());
				break;

			case 'lastmonth':
				$month_start = clone Factory::getDate(strtotime("first day of last month"));
				$month_end   = clone Factory::getDate(strtotime("last day of last month"));

				$date = $db->q($month_start->toSql()) . " AND " . $db->q($month_end->toSql());
				break;

			case 'month':
				$month_start = clone Factory::getDate(date('Y-m-01'));
				$month_end   = clone Factory::getDate(date('Y-m-t'));

				$date = $db->q($month_start->toSql()) . "AND " . $db->q($month_end->toSql());
				break;

			case 'week':
				$week_start = clone Factory::getDate(strtotime('Sunday last week'));
				$week_end   = clone Factory::getDate(strtotime('Monday this week'));

				$date = $db->q($week_start->toSql()) . " AND " . $db->q($week_end->toSql());
				break;

			case 'day':
				$day_start = clone Factory::getDate(date('Y-m-d') . ' 00:00:00');
				$day_end   = clone Factory::getDate(date('Y-m-d') . ' 23:59:59');

				$date = $db->q($day_start->toSql()) . " AND " . $db->q($day_end->toSql());
				break;
		}

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__ars_log') . ' AS ' . $db->qn('l'))
			->where($db->qn('l') . '.' . $db->qn('authorized') . ' = ' . $db->q(1));

		if (!$allTime)
		{
			$query
				->where($db->qn('l') . '.' . $db->qn('accessed_on') . ' BETWEEN ' . $date);
		}

		return (int) ($db->setQuery($query)->loadResult() ?: 0);
	}

	/**
	 * Returns the data for the monthly-daily report of downloads
	 *
	 * @return  array  In the format [[date => numberOfDownloads], ...]
	 * @throws  \Exception
	 */
	public function getMonthlyStats(): array
	{
		$db = $this->getDatabase();

		$now        = clone Factory::getDate();
		$last_month = clone Factory::getDate();
		$last_month->sub(new DateInterval('P35D'));

		$query = $db->getQuery(true)
			->select([
				'DATE(' . $db->qn('accessed_on') . ') AS ' . $db->qn('day'),
				'COUNT(*) AS ' . $db->qn('dl'),
			])
			->from($db->qn('#__ars_log'))
			->where($db->qn('accessed_on') . ' BETWEEN ' . $db->q($last_month->toSql()) . ' AND ' . $db->q($now->toSql()))
			->order($db->qn('accessed_on') . ' ASC');

		if ($db->name === 'pgsql')
		{
			$query->group('EXTRACT(DOY FROM TIMESTAMP accessed_on)');
		}
		else
		{
			$query->group('DAYOFYEAR(' . $db->qn('accessed_on') . ')');
		}

		$data = $db->setQuery($query)->loadAssocList('day') ?: [];

		$nowParts = getdate();
		$today    = mktime(0, 0, 0, $nowParts['mon'], $nowParts['mday'], $nowParts['year']);
		$ret      = [];

		for ($i = 30; $i >= 0; $i--)
		{
			$thisDay       = date('Y-m-d', $today - $i * 86400);
			$ret[$thisDay] = array_key_exists($thisDay, $data) ? $data[$thisDay]['dl'] : 0;
		}

		return $ret;
	}
}