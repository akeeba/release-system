<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelCpanels extends F0FModel
{
	/**
	 * Get an array of icon definitions for the Control Panel
	 *
	 * @return array
	 */
	public function getIconDefinitions()
	{
		return $this->loadIconDefinitions(JPATH_COMPONENT_ADMINISTRATOR.'/views');
	}

	/**
	 * Loads the icon definitions form the views.ini file
	 * @param string $path Where the views.ini file can be found
	 */
	private function loadIconDefinitions($path)
	{
		$ret = array();

		if(!@file_exists($path.'/views.ini')) return $ret;

		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/ini.php';

		$ini_data = ArsHelperINI::parse_ini_file($path.'/views.ini', true);
		if(!empty($ini_data))
		{
			foreach($ini_data as $view => $def)
			{
				if(array_key_exists('hidden', $def))
					if(in_array(strtolower($def['hidden']),array('true','yes','on','1')))
						continue;
				$task = array_key_exists('task',$def) ? $def['task'] : null;
				$ret[$def['group']][] = $this->_makeIconDefinition($def['icon'], JText::_($def['label']), $view, $task);
			}
		}

		return $ret;
	}

	/**
	 * Creates an icon definition entry
	 *
	 * @param string $iconFile The filename of the icon on the GUI button
	 * @param string $label The label below the GUI button
	 * @param string $view The view to fire up when the button is clicked
	 * @return array The icon definition array
	 */
	public function _makeIconDefinition($iconFile, $label, $view = null, $task = null )
	{
		return array(
			'icon'	=> $iconFile,
			'label'	=> $label,
			'view'	=> $view,
			'task'	=> $task
		);
	}

	/**
	 * Gets popular items within a time frame
	 * @param int $itemCount How many records to retrieve ("Top X")
	 * @param string $from MySQL date expression marking the start of the time frame. Omit to search all.
	 * @param string $to MySQL date expression marking the end of the time frame. Omit to search all.
	 */
	private function getPopular($itemCount = 5, $from = null, $to = null)
	{
		$db = $this->getDBO();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('l').'.'.$db->qn('item_id'),
				'COUNT(*) AS '.$db->qn('dl')
			))->from($db->qn('#__ars_log').' AS '.$db->qn('l'))
			->where($db->qn('l').'.'.$db->qn('authorized').' = '.$db->q(1))
			->group($db->qn('item_id'))
			->order($db->qn('dl').' DESC');


		$noTimeLimits = (is_null($from) || is_null($to));
		if(!$noTimeLimits) {
			$query->where($db->qn('l').'.'.$db->qn('accessed_on').' BETWEEN '.$db->q($from).' AND '.$db->q($to));
		}

		$db->setQuery($query, 0, $itemCount);
		$items = $db->loadAssocList('item_id');

		if(empty($items)) return null;

		$idLimit = implode(',', array_keys($items));

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('i').'.'.$db->qn('id').' AS '.$db->qn('item_id'),
				$db->qn('i').'.'.$db->qn('title'),
				$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('category'),
				$db->qn('r').'.'.$db->qn('version'),
				$db->qn('r').'.'.$db->qn('maturity'),
				$db->qn('i').'.'.$db->qn('updatestream'),
			))->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
			->join('INNER', $db->qn('#__ars_releases').' AS '.$db->qn('r').' ON('.
					$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id').')')
			->join('INNER', $db->qn('#__ars_categories').' AS '.$db->qn('c').' ON('.
					$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id').')')
			->where($db->qn('i').'.'.$db->qn('id').' IN('.$idLimit.')')
		;
		$db->setQuery($query);
		$infoList = $db->loadAssocList('item_id');

		$ret = array();
		foreach($items as $item)
		{
			$info = array_key_exists($item['item_id'],$infoList) ? $infoList[$item['item_id']] : null;
			if(is_array($info)) {
				$ret[] = (object)array_merge($info, $item);
			}
		}

		return $ret;

	}


	/**
	 * Returns the most popular items of all times
	 */
	public function getAllTimePopular($itemCount = 5)
	{
		return $this->getPopular($itemCount);
	}

	/**
	 * Returns the most popular items of the current week
	 */
	public function getWeekPopular($itemCount = 5)
	{
        $now     = new JDate();
        $weekago = new JDate(strtotime('-7 days'));
		return $this->getPopular($itemCount, $weekago->toSql(), $now->toSql());
	}

	/**
	 * Returns the total number of (authorized) downloads within a specific time frame.
	 */
	public function getNumDownloads($interval)
	{
		$db = $this->getDbo();

		$interval = strtolower($interval);
		$alltime = false;
		switch($interval)
		{
			case 'alltime':
			default:
				$alltime = true;
				break;

			case 'year':
                $year_start = new JDate(date('Y-01-01'));
                $year_end   = new JDate(date('Y-12-31'));

				$date = $db->q($year_start->toSql())." AND ".$db->q($year_end->toSql());
				break;

			case 'lastmonth':
                $month_start = new JDate(strtotime("first day of last month"));
                $month_end   = new JDate(strtotime("last day of last month"));

				$date = $db->q($month_start->toSql())." AND ".$db->q($month_end->toSql());
				break;

			case 'month':
                $month_start = new JDate(date('Y-m-01'));
                $month_end   = new JDate(date('Y-m-t'));

				$date = $db->q($month_start->toSql()). "AND ".$db->q($month_end->toSql());
				break;

			case 'week':
                $week_start = new JDate(strtotime('Sunday last week'));
                $week_end   = new JDate(strtotime('Monday this week'));


                //$date = "DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 1) DAY AND DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 7) DAY";
				$date = $db->q($week_start->toSql())." AND ".$db->q($week_end->toSql());
				break;

			case 'day':
                $day_start = new JDate(date('Y-m-d').' 00:00:00');
                $day_end   = new JDate(date('Y-m-d').' 23:59:59');

				$date = $db->q($day_start->toSql())." AND ".$db->q($day_end->toSql());
				break;
		}

		if(!$alltime) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__ars_log').' AS '.$db->qn('l'))
				->where($db->qn('l').'.'.$db->qn('accessed_on').' BETWEEN '.$date)
				->where($db->qn('l').'.'.$db->qn('authorized').' = '.$db->q(1));
		} else {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__ars_log').' AS '.$db->qn('l'))
				->where($db->qn('l').'.'.$db->qn('authorized').' = '.$db->q(1));
		}
		$db->setQuery($query);
		return $db->loadResult();
	}


	/**
	 * Returns downloads per country to seed the map
	 */
	public function getChartData()
	{
		$db	= $this->getDBO();

        $now = new JDate();

        // I need to do this since if I'm on March 30th and I go back of a month I would got February 30th
        // that will we shifted to March 2nd. This is not a bug (!!!) it's the expected behavior of PHP (!!!!!!!)
        if (date('d') > date('d', strtotime('last day of -1 month')))
        {
            $last_month = new JDate(date('Y-m-d', strtotime('last day of -1 month')));
        }
        else
        {
            $last_month = new JDate(date('Y-m-d', strtotime('-1 month')));
        }

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('country'),
				'COUNT('.$db->qn('id').') AS '.$db->qn('dl')
			))
			->from($db->qn('#__ars_log'))
			->where($db->qn('country').' <> '.$db->q(''))
			->where($db->qn('accessed_on').' BETWEEN '.$db->q($last_month->toSql()).' AND '.$db->q($now->toSql()))
			->group($db->qn('country'))
			;
		$db->setQuery($query);
		$data = $db->loadObjectList();
		$ret = array();
		if(!empty($data)) foreach($data as $item)
		{
			$ret[$item->country] = $item->dl * 1;
		}
		return $ret;
	}

	/**
	 * Returns the data for the monthly-daily report of downloads
	 */
	public function getMonthlyStats()
	{
		$db = $this->getDBO();

        $now = new JDate();

        // I need to do this since if I'm on March 30th and I go back of a month I would got February 30th
        // that will we shifted to March 2nd. This is not a bug (!!!) it's the expected behavior of PHP (!!!!!!!)
        if (date('d') > date('d', strtotime('last day of -1 month')))
        {
            $last_month = new JDate(date('Y-m-d', strtotime('last day of -1 month')));
        }
        else
        {
            $last_month = new JDate(date('Y-m-d', strtotime('-1 month')));
        }

		$query = $db->getQuery(true)
			->select(array(
				'DATE('.$db->qn('accessed_on').') AS '.$db->qn('day'),
				'COUNT(*) AS '.$db->qn('dl')
			))
			->from($db->qn('#__ars_log'))
            ->where($db->qn('accessed_on').' BETWEEN '.$db->q($last_month->toSql()).' AND '.$db->q($now->toSql()))
			->order($db->qn('accessed_on').' ASC')
        ;

        if($db->name == 'postgresql')
        {
            $query->group('EXTRACT(DOY FROM TIMESTAMP accessed_on)');
        }
        else
        {
            $query->group('DAYOFYEAR('.$db->qn('accessed_on').')');
        }

		$db->setQuery($query);

		$data = $db->loadAssocList('day');
		if(is_null($data)) $data = array();

		$nowParts = getdate();
		$today = mktime(0,0,0,$nowParts['mon'],$nowParts['mday'],$nowParts['year']);
		$ret = array();
		for($i = 30; $i >= 0; $i--) {
			$thisDay = date('Y-m-d',$today - $i * 86400);
			if(array_key_exists($thisDay, $data)) {
				$ret[$thisDay] = $data[$thisDay]['dl'];
			} else {
				$ret[$thisDay] = 0;
			}
		}

		return $ret;
	}

	/**
	 * Do we have the Akeeba GeoIP provider plugin installed?
	 *
	 * @return  boolean  False = not installed, True = installed
	 */
	public function hasGeoIPPlugin()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('plugin'))
			->where($db->qn('folder') . ' = ' . $db->q('system'))
			->where($db->qn('element') . ' = ' . $db->q('akgeoip'))
			->where($db->qn('enabled') . ' = 1');
		$db->setQuery($query);
		$result = $db->loadResult();

		return ($result != 0);
	}

	/**
	 * Does the GeoIP database need update?
	 *
	 * @param   integer  $maxAge  The maximum age of the db in days (default: 15)
	 *
	 * @return  boolean
	 */
	public function dbNeedsUpdate($maxAge = 15)
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
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  void
	 */
	public function refreshUpdateSite()
	{
		// Create the update site definition we want to store to the database
		$update_site = array(
			'name'		=> 'Akeeba Release System',
			'type'		=> 'extension',
			'location'	=> 'http://cdn.akeebabackup.com/updates/ars.xml',
			'enabled'	=> 1,
			'last_check_timestamp'	=> 0,
			'extra_query'	=> null
		);

		if (version_compare(JVERSION, '3.2.1', 'lt'))
		{
			unset($update_site['extra_query']);
		}

		$db = $this->getDbo();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q('com_ars'));
		$db->setQuery($query);

		$extension_id = $db->loadResult();

		if (empty($extension_id))
		{
			return;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extension_id));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);

		if (!count($updateSiteIDs))
		{
			// No update sites defined. Create a new one.
			$newSite = (object)$update_site;
			$db->insertObject('#__update_sites', $newSite);

			$id = $db->insertid();

			$updateSiteExtension = (object)array(
				'update_site_id'	=> $id,
				'extension_id'		=> $extension_id,
			);
			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);
		}
		else
		{
			// Loop through all update sites
			foreach ($updateSiteIDs as $id)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$aSite = $db->loadObject();

				// Does the name and location match?
				if (($aSite->name == $update_site['name']) && ($aSite->location == $update_site['location']))
				{
					continue;
				}

				$update_site['update_site_id'] = $id;
				$newSite = (object)$update_site;
				$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
			}
		}
	}

	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return ArsModelCpanels
	 */
	public function checkAndFixDatabase()
	{
		// Install or update database
		$dbFilePath = JPATH_ADMINISTRATOR . '/components/com_ars/sql';
		if (!class_exists('AkeebaDatabaseInstaller'))
		{
			require_once $dbFilePath . '/dbinstaller.php';
		}
		$dbInstaller = new AkeebaDatabaseInstaller(JFactory::getDbo());
		$dbInstaller->setXmlDirectory($dbFilePath . '/xml');
		$dbInstaller->updateSchema();

		return $this;
	}
}
