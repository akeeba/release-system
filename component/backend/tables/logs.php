<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableLogs extends FOFTable
{
	/**
	 * Instantiate the table object
	 * 
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct( &$db )
	{
		parent::__construct( '#__ars_log', 'id', $db );
		
		$this->columnAlias = array(
			'enabled'		=> 'published',
			'slug'			=> 'alias',
			'created_on'	=> 'created',
			'modified_on'	=> 'modified',
			'locked_on'		=> 'checked_out_time',
			'locked_by'		=> 'checked_out',
		);
	}

	/**
	 * Checks the record for validity
	 * 
	 * @return int True if the record is valid
	 */
	function check()
	{
		if(empty($this->user_id))
		{
			$user = JFactory::getUser();
			$this->user_id = $user->id;
		}

		if(empty($this->item_id))
		{
			$this->item_id = JRequest::getInt('id',0);
		}

		if($this->accessed_on == '0000-00-00 00:00:00')
		{
			jimport('joomla.utilities.date');
			$date = new JDate();
			$this->accessed_on = $date->toMySQL();
		}

		if(empty($this->referer))
		{
			if(isset($_SERVER['HTTP_REFERER'])) {
				$this->referer = $_SERVER['HTTP_REFERER'];
			}
		}

		if(empty($this->ip))
		{
			if(isset($_SERVER['REMOTE_ADDR']))
			{
				$this->ip = $_SERVER['REMOTE_ADDR'];
				require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/geoip.php';
				$gi = geoip_open(JPATH_ADMINISTRATOR.'/components/com_ars/assets/geoip/GeoIP.dat',GEOIP_STANDARD);
				$this->country = geoip_country_code_by_addr($gi, $this->ip);
				geoip_close($gi);
			}
		}

		return true;
	}
}