<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelCpanels extends FOFModel
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
				$date = "makedate(year(current_timestamp), 1) AND makedate(year(current_timestamp), 1) + interval 1 year - interval 1 day";
				break;
			
			case 'lastmonth':
				$date = "LAST_DAY(CURRENT_TIMESTAMP) - INTERVAL 2 MONTH + INTERVAL 1 DAY AND LAST_DAY(CURRENT_TIMESTAMP) - INTERVAL 1 MONTH";
				break;

			case 'month':
				$date = "LAST_DAY(CURRENT_TIMESTAMP) - INTERVAL 1 MONTH + INTERVAL 1 DAY AND LAST_DAY(CURRENT_TIMESTAMP)";
				break;

			case 'week':
				$date = "DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 1) DAY AND DATE(CURRENT_TIMESTAMP) - INTERVAL (DAYOFWEEK(CURRENT_TIMESTAMP) - 7) DAY";
				break;

			case 'day':
				$date = "DATE(CURRENT_TIMESTAMP) AND DATE(CURRENT_TIMESTAMP) + INTERVAL 24 HOUR - INTERVAL 1 SECOND";
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
		
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('country'),
				'COUNT('.$db->qn('id').') AS '.$db->qn('dl')
			))
			->from($db->qn('#__ars_log'))
			->where($db->qn('country').' <> '.$db->q(''))
			->where($db->qn('accessed_on').' BETWEEN CURRENT_TIMESTAMP - INTERVAL 1 MONTH AND CURRENT_TIMESTAMP')
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
		
		$query = $db->getQuery(true)
			->select(array(
				'DATE('.$db->qn('accessed_on').') AS '.$db->qn('day'),
				'COUNT(*) AS '.$db->qn('dl')
			))
			->from($db->qn('#__ars_log'))
			->where($db->qn('accessed_on').' BETWEEN CURRENT_TIMESTAMP - INTERVAL 1 MONTH AND CURRENT_TIMESTAMP')
			->group('DAYOFYEAR('.$db->qn('accessed_on').')')
			->order($db->qn('accessed_on').' ASC')
		;
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
}
