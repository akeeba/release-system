<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'base.php';

class TableLogs extends ArsTable
{
	var $id = 0;
	var $user_id = 0;
	var $item_id = 0;
	var $accessed_on = '0000-00-00 00:00:00';
	var $referer = '';
	var $ip = '';
	var $country = '';
	var $authorized = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_log', 'id', $db );
	}

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
				require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'geoip.php';
				$gi = geoip_open(JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'geoip'.DS.'GeoIP.dat',GEOIP_STANDARD);
				$this->country = geoip_country_code_by_addr($gi, $this->ip);
				geoip_close($gi);
			}
		}

		return true;
	}
}