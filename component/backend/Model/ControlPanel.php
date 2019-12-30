<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Database\Installer;
use FOF30\Model\Model;
use Joomla\CMS\Uri\Uri;
use JRegistry;

class ControlPanel extends Model
{
	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return  $this
	 * @throws \Exception
	 */
	public function checkAndFixDatabase(): self
	{
		$db = $this->container->platform->getDbo();

		$dbInstaller = new Installer($db, JPATH_ADMINISTRATOR . '/components/com_ars/sql/xml');
		$dbInstaller->updateSchema();

		return $this;
	}

	/**
	 * Save some magic variables we need
	 *
	 * @return  $this
	 */
	public function saveMagicVariables(): self
	{
		// Store the URL to this site
		$db = $this->container->platform->getDbo();
		$query = $db->getQuery(true)
			->select('params')
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . '=' . $db->q('com_ars'))
			->where($db->qn('type') . '=' . $db->q('component'));
		$db->setQuery($query);
		$rawparams = $db->loadResult();

		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		$siteURL_stored = $params->get('siteurl', '');
		$siteURL_target = str_replace('/administrator', '', Uri::base());

		if ($siteURL_target != $siteURL_stored)
		{
			$params->set('siteurl', $siteURL_target);
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->set($db->qn('params') . '=' . $db->q($params->toString()))
				->where($db->qn('element') . '=' . $db->q('com_ars'))
				->where($db->qn('type') . '=' . $db->q('component'));
			$db->setQuery($query);
			$db->execute();
		}

		return $this;
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
		$db = $this->container->db;

		$interval = strtolower($interval);
		$allTime = false;
		$date = '';

		switch ($interval)
		{
			case 'alltime':
			default:
				$allTime = true;
				break;

			case 'year':
				$year_start = $this->container->platform->getDate(date('Y-01-01'));
				$year_end = $this->container->platform->getDate(date('Y-12-31'));

				$date = $db->q($year_start->toSql()) . " AND " . $db->q($year_end->toSql());
				break;

			case 'lastmonth':
				$month_start = $this->container->platform->getDate(strtotime("first day of last month"));
				$month_end = $this->container->platform->getDate(strtotime("last day of last month"));

				$date = $db->q($month_start->toSql()) . " AND " . $db->q($month_end->toSql());
				break;

			case 'month':
				$month_start = $this->container->platform->getDate(date('Y-m-01'));
				$month_end = $this->container->platform->getDate(date('Y-m-t'));

				$date = $db->q($month_start->toSql()) . "AND " . $db->q($month_end->toSql());
				break;

			case 'week':
				$week_start = $this->container->platform->getDate(strtotime('Sunday last week'));
				$week_end = $this->container->platform->getDate(strtotime('Monday this week'));

				//$date = "DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 1) DAY AND DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 7) DAY";
				$date = $db->q($week_start->toSql()) . " AND " . $db->q($week_end->toSql());
				break;

			case 'day':
				$day_start = $this->container->platform->getDate(date('Y-m-d') . ' 00:00:00');
				$day_end = $this->container->platform->getDate(date('Y-m-d') . ' 23:59:59');

				$date = $db->q($day_start->toSql()) . " AND " . $db->q($day_end->toSql());
				break;
		}

		if (!$allTime)
		{
			$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__ars_log') . ' AS ' . $db->qn('l'))
						->where($db->qn('l') . '.' . $db->qn('accessed_on') . ' BETWEEN ' . $date)
						->where($db->qn('l') . '.' . $db->qn('authorized') . ' = ' . $db->q(1));
		}
		else
		{
			$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__ars_log') . ' AS ' . $db->qn('l'))
						->where($db->qn('l') . '.' . $db->qn('authorized') . ' = ' . $db->q(1));
		}

		$db->setQuery($query);

		return (int) ($db->loadResult());
	}

	/**
	 * Returns the data for the monthly-daily report of downloads
	 *
	 * @return  array  In the format [[date => numberOfDownloads], ...]
	 * @throws \Exception
	 */
	public function getMonthlyStats(): array
	{
		$db = $this->container->db;

		$now        = $this->container->platform->getDate();
		$last_month = $this->container->platform->getDate();
		$last_month->sub(new \DateInterval('P35D'));

		$query = $db->getQuery(true)
			->select([
				'DATE(' . $db->qn('accessed_on') . ') AS ' . $db->qn('day'),
				'COUNT(*) AS ' . $db->qn('dl'),
			])
			->from($db->qn('#__ars_log'))
			->where($db->qn('accessed_on') . ' BETWEEN ' . $db->q($last_month->toSql()) . ' AND ' . $db->q($now->toSql()))
			->order($db->qn('accessed_on') . ' ASC');

		if ($db->name == 'postgresql')
		{
			$query->group('EXTRACT(DOY FROM TIMESTAMP accessed_on)');
		}
		else
		{
			$query->group('DAYOFYEAR(' . $db->qn('accessed_on') . ')');
		}

		$db->setQuery($query);
		$data = $db->loadAssocList('day');

		if (is_null($data))
		{
			$data = [];
		}

		$nowParts = getdate();
		$today    = mktime(0, 0, 0, $nowParts['mon'], $nowParts['mday'], $nowParts['year']);
		$ret      = [];

		for ($i = 30; $i >= 0; $i--)
		{
			$thisDay = date('Y-m-d', $today - $i * 86400);

			if (array_key_exists($thisDay, $data))
			{
				$ret[$thisDay] = $data[$thisDay]['dl'];
			}
			else
			{
				$ret[$thisDay] = 0;
			}
		}

		return $ret;
	}

	/**
	 * Checks if there is at least one menu entry that shows all the categories.
	 * This is needed because otherwise JRoute won't find any suitable menu
	 *
	 * @return bool
	 */
	public static function needsCategoriesMenu(): bool
	{
		$db = Container::getInstance('com_ars')->db;

		$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from('#__menu')
				->where($db->qn('link').' = '.$db->q('index.php?option=com_ars&view=Categories&layout=repository'))
				->where($db->qn('published').' = '.$db->q(1));

		return !(bool) $db->setQuery($query)->loadResult();
	}
}
