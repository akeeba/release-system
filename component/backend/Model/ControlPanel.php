<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Helper\IniParser;
use FOF30\Container\Container;
use FOF30\Database\Installer;
use FOF30\Model\Model;
use JRegistry;
use JText;

class ControlPanel extends Model
{
	/**
	 * Do we have the Akeeba GeoIP provider plugin installed?
	 *
	 * @return  boolean  False = not installed, True = installed
	 */
	public function hasGeoIPPlugin()
	{
		static $result = null;

		if (is_null($result))
		{
			$db = $this->container->db;

			$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where($db->qn('type') . ' = ' . $db->q('plugin'))
						->where($db->qn('folder') . ' = ' . $db->q('system'))
						->where($db->qn('element') . ' = ' . $db->q('akgeoip'));
			$db->setQuery($query);
			$result = $db->loadResult();
		}

		return ($result != 0);
	}

	/**
	 * Does the GeoIP database need update?
	 *
	 * @param   integer  $maxAge  The maximum age of the db in days (default: 15)
	 *
	 * @return  boolean
	 */
	public function GeoIPDBNeedsUpdate($maxAge = 15)
	{
		$needsUpdate = false;

		if (!$this->hasGeoIPPlugin())
		{
			return $needsUpdate;
		}

		// Get the modification time of the database file
		$filePath = JPATH_ROOT . '/plugins/system/akgeoip/db/GeoLite2-Country.mmdb';
		$modTime = @filemtime($filePath);

		// This is now
		$now = time();

		// Minimum time difference we want (15 days) in seconds
		if ($maxAge <= 0)
		{
			$maxAge = 15;
		}

		$threshold = $maxAge * 24 * 3600;

		// Do we need an update?
		$needsUpdate = ($now - $modTime) > $threshold;

		return $needsUpdate;
	}

	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return  $this
	 */
	public function checkAndFixDatabase()
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
	public function saveMagicVariables()
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
		$siteURL_target = str_replace('/administrator', '', \JUri::base());

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
	 * Sets the value of a component parameter in the #__extensions table
	 *
	 * @param   string  $parameter  The parameter name
	 * @param   string  $value      The parameter value
	 *
	 * @return  void
	 */
	public function setComponentParameter($parameter, $value)
	{
		// Fetch the component parameters
		$db = $this->container->db;
		$sql = $db->getQuery(true)
		          ->select($db->qn('params'))
		          ->from($db->qn('#__extensions'))
		          ->where($db->qn('type').' = '.$db->q('component'))
		          ->where($db->qn('element').' = '.$db->q('com_ars'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();

		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		// Set the show2copromo parameter to 0
		$params->set($parameter, $value);

		// Save the component parameters
		$data = $params->toString('JSON');

		$sql = $db->getQuery(true)
		          ->update($db->qn('#__extensions'))
		          ->set($db->qn('params').' = '.$db->q($data))
		          ->where($db->qn('type').' = '.$db->q('component'))
		          ->where($db->qn('element').' = '.$db->q('com_ars'));

		$db->setQuery($sql);
		$db->execute();
	}

	/**
	 * Gets popular items within a specific time period
	 *
	 * @param   int     $itemCount  How many records to retrieve ("Top X")
	 * @param   string  $from       MySQL date expression marking the start of the time frame. Omit to search all.
	 * @param   string  $to         MySQL date expression marking the end of the time frame. Omit to search all.
	 *
	 * @return  \stdClass[]
	 */
	private function getPopular($itemCount = 5, $from = null, $to = null)
	{
		$db = $this->container->db;

		$query = $db->getQuery(true)
					->select(array(
						$db->qn('l') . '.' . $db->qn('item_id'),
						'COUNT(*) AS ' . $db->qn('dl')
					))->from($db->qn('#__ars_log') . ' AS ' . $db->qn('l'))
					->where($db->qn('l') . '.' . $db->qn('authorized') . ' = ' . $db->q(1))
					->group($db->qn('item_id'))
					->order($db->qn('dl') . ' DESC');

		$noTimeLimits = (is_null($from) || is_null($to));

		if (!$noTimeLimits)
		{
			$query->where($db->qn('l') . '.' . $db->qn('accessed_on') . ' BETWEEN ' . $db->q($from) . ' AND ' . $db->q($to));
		}

		$db->setQuery($query, 0, $itemCount);
		$items = $db->loadAssocList('item_id');

		if (empty($items))
		{
			return null;
		}

		$idLimit = implode(',', array_keys($items));

		$query = $db->getQuery(true)
					->select(array(
						$db->qn('i') . '.' . $db->qn('id') . ' AS ' . $db->qn('item_id'),
						$db->qn('i') . '.' . $db->qn('title'),
						$db->qn('c') . '.' . $db->qn('title') . ' AS ' . $db->qn('category'),
						$db->qn('r') . '.' . $db->qn('version'),
						$db->qn('r') . '.' . $db->qn('maturity'),
						$db->qn('i') . '.' . $db->qn('updatestream'),
					))->from($db->qn('#__ars_items') . ' AS ' . $db->qn('i'))
					->join('INNER', $db->qn('#__ars_releases') . ' AS ' . $db->qn('r') . ' ON(' .
						$db->qn('r') . '.' . $db->qn('id') . ' = ' . $db->qn('i') . '.' . $db->qn('release_id') . ')')
					->join('INNER', $db->qn('#__ars_categories') . ' AS ' . $db->qn('c') . ' ON(' .
						$db->qn('c') . '.' . $db->qn('id') . ' = ' . $db->qn('r') . '.' . $db->qn('category_id') . ')')
					->where($db->qn('i') . '.' . $db->qn('id') . ' IN(' . $idLimit . ')');
		$db->setQuery($query);
		$infoList = $db->loadAssocList('item_id');

		$ret = array();

		foreach ($items as $item)
		{
			$info = array_key_exists($item['item_id'], $infoList) ? $infoList[$item['item_id']] : null;

			if (is_array($info))
			{
				$ret[] = (object)array_merge($info, $item);
			}
		}

		return $ret;
	}

	/**
	 * Returns the most popular items of all times
	 *
	 * @param   int  $itemCount  How many items to return, default 5
	 *
	 * @return  \stdClass[]
	 */
	public function getAllTimePopular($itemCount = 5)
	{
		return $this->getPopular($itemCount);
	}

	/**
	 * Returns the most popular items of the current week
	 *
	 * @param   int  $itemCount  How many items to return, default 5
	 *
	 * @return  \stdClass[]
	 */
	public function getWeekPopular($itemCount = 5)
	{
		$now = $this->container->platform->getDate();
		$weekAgo = $this->container->platform->getDate(strtotime('-7 days'));

		return $this->getPopular($itemCount, $weekAgo->toSql(), $now->toSql());
	}

	/**
	 * Returns the total number of (authorized) downloads within a specific time period.
	 *
	 * @param   string  $interval  The time interval to use: alltime, year, lastmonth, month, week, day
	 *
	 * @return  int
	 */
	public function getNumDownloads($interval)
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

		return $db->loadResult();
	}

	/**
	 * Returns downloads per country to seed the map
	 *
	 * @return  array  In the format [[country => numberOfDownloads], ...]
	 */
	public function getChartData()
	{
		$db = $this->container->db;

		$now = $this->container->platform->getDate();

		// I need to do this since if I'm on March 30th and I go back of a month I would got February 30th
		// that will we shifted to March 2nd. This is not a bug (!!!) it's the expected behavior of PHP (!!!!!!!)
		if (date('d') > date('d', strtotime('last day of -1 month')))
		{
			$last_month = $this->container->platform->getDate(date('Y-m-d', strtotime('last day of -1 month')));
		}
		else
		{
			$last_month = $this->container->platform->getDate(date('Y-m-d', strtotime('-1 month')));
		}

		$query = $db->getQuery(true)
					->select(array(
						$db->qn('country'),
						'COUNT(' . $db->qn('id') . ') AS ' . $db->qn('dl')
					))
					->from($db->qn('#__ars_log'))
					->where($db->qn('country') . ' <> ' . $db->q(''))
					->where($db->qn('accessed_on') . ' BETWEEN ' . $db->q($last_month->toSql()) . ' AND ' . $db->q($now->toSql()))
					->group($db->qn('country'));

		$db->setQuery($query);
		$data = $db->loadObjectList();
		$ret = array();

		if (!empty($data))
		{
			foreach ($data as $item)
			{
				$ret[$item->country] = $item->dl * 1;
			}
		}

		return $ret;
	}

	/**
	 * Returns the data for the monthly-daily report of downloads
	 *
	 * @return  array  In the format [[date => numberOfDownloads], ...]
	 */
	public function getMonthlyStats()
	{
		$db = $this->container->db;

		$now = $this->container->platform->getDate();

		// I need to do this since if I'm on March 30th and I go back of a month I would got February 30th
		// that will we shifted to March 2nd. This is not a bug (!!!) it's the expected behavior of PHP (!!!!!!!)
		if (date('d') > date('d', strtotime('last day of -1 month')))
		{
			$last_month = $this->container->platform->getDate(date('Y-m-d', strtotime('last day of -1 month')));
		}
		else
		{
			$last_month = $this->container->platform->getDate(date('Y-m-d', strtotime('-1 month')));
		}

		$query = $db->getQuery(true)
					->select(array(
						'DATE(' . $db->qn('accessed_on') . ') AS ' . $db->qn('day'),
						'COUNT(*) AS ' . $db->qn('dl')
					))
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
			$data = array();
		}

		$nowParts = getdate();
		$today = mktime(0, 0, 0, $nowParts['mon'], $nowParts['mday'], $nowParts['year']);
		$ret = array();

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
	public static function needsCategoriesMenu()
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
