<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

/**
 * The Control Panel model
 *
 */
class ArsModelCpanel extends JModel
{
	/**
	 * Contructor; dummy for now
	 *
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get an array of icon definitions for the Control Panel
	 *
	 * @return array
	 */
	public function getIconDefinitions()
	{
		return $this->loadIconDefinitions(JPATH_COMPONENT_ADMINISTRATOR.DS.'views');
	}

	/**
	 * Loads the icon definitions form the views.ini file
	 * @param string $path Where the views.ini file can be found
	 */
	private function loadIconDefinitions($path)
	{
		$ret = array();

		if(!@file_exists($path.DS.'views.ini')) return $ret;

		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'ini.php';

		$ini_data = ArsHelperINI::parse_ini_file($path.DS.'views.ini', true);
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
		$itemCountEsc = (int)$itemCount;
		
		$noTimeLimits = (is_null($from) || is_null($to));
		if(!$noTimeLimits) {
			$from = $db->getEscaped($from);
			$to = $db->getEscaped($to);
			$where = "AND (`l`.`accessed_on` BETWEEN $from AND $to)";
		} else {
			$where = '';
		}
		$sql = <<<ENDSQL
SELECT `l`.`item_id`, COUNT(*) as `dl`
  FROM `#__ars_log` AS `l`
  WHERE
  	`l`.`authorized` = 1
  $where 
  GROUP BY `item_id` 
  ORDER BY `dl` DESC 
  LIMIT 0, $itemCountEsc;
ENDSQL;
		$db->setQuery($sql);
		$items = $db->loadAssocList('item_id');
		
		if(empty($items)) return null;
		
		$idLimit = implode(',', array_keys($items));
		
$sql = <<<ENDSQL
SELECT `i`.`id` AS `item_id`, `i`.`title`, `c`.`title` as `category`, `r`.`version`, `r`.`maturity`, `i`.`updatestream` 
  FROM `#__ars_items` AS `i` 
  INNER JOIN `#__ars_releases` AS `r` 
  ON(`r`.`id` = `i`.`release_id`) 
  INNER JOIN `#__ars_categories` AS `c` 
  ON(`c`.`id` = `r`.`category_id`)
  WHERE `i`.`id` IN ($idLimit)
ENDSQL;
		$db->setQuery($sql);
		$infoList = $db->loadAssocList('item_id');
		
		$ret = array();
		foreach($items as $item)
		{
			$info = $infoList[$item['item_id']];
			$ret[] = (object)array_merge($info, $item);
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
		return $this->getPopular($itemCount,'CURRENT_TIMESTAMP - INTERVAL 7 DAY','CURRENT_TIMESTAMP');
	}

	/**
	 * Returns the total number of (authorized) downloads within a specific time frame.
	 */
	public function getNumDownloads($interval)
	{
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
		$db = $this->getDBO();
		if(!$alltime) {
			$db->setQuery( <<<ENDSQL
SELECT
	COUNT(*)
FROM
	`#__ars_log` AS `l`
WHERE
	`l`.`accessed_on` BETWEEN $date
	AND `l`.`authorized` = 1
ENDSQL
);
		} else {
			$db->setQuery( <<<ENDSQL
SELECT
	COUNT(*)
FROM
	`#__ars_log` AS `l`
WHERE
	`l`.`authorized` = 1
ENDSQL
);
		}
		return $db->loadResult();
	}

	
	/**
	 * Returns downloads per country to seed the map
	 */
	public function getChartData()
	{
		$db	= $this->getDBO();
		$db->setQuery( <<<ENDSQL
SELECT
  `country`, COUNT(id) as `dl`
FROM
  `#__ars_log`
WHERE
  `country` <> ''
  AND `accessed_on` BETWEEN CURRENT_TIMESTAMP - INTERVAL 1 MONTH AND CURRENT_TIMESTAMP
GROUP BY `country`
ENDSQL
);
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
		$db->setQuery( <<<ENDSQL
SELECT DATE(`accessed_on`) as `day`, COUNT(*) AS `dl`
FROM `#__ars_log`
WHERE `accessed_on` BETWEEN CURRENT_TIMESTAMP - INTERVAL 1 MONTH AND CURRENT_TIMESTAMP
GROUP BY DAYOFYEAR(`accessed_on`)
order by accessed_on ASC
ENDSQL
);
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
	
}