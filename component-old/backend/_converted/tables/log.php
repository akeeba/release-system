<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableLog extends F0FTable
{
	/**
	 * Instantiate the table object
	 *
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct($table, $key, &$db)
	{
		parent::__construct('#__ars_log', 'id', $db);

		@include_once __DIR__ . '/../helpers/ip.php';

		$this->_columnAlias = array(
			'enabled'     => 'published',
			'slug'        => 'alias',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		);
	}

	/**
	 * Checks the record for validity
	 *
	 * @return int True if the record is valid
	 */
	function check()
	{
		if (empty($this->user_id))
		{
			$user = JFactory::getUser();
			$this->user_id = $user->id;
		}

		if (empty($this->item_id))
		{
			$this->item_id = $this->input->getInt('id', 0);
		}

		if (empty($this->accessed_on) || ($this->accessed_on == '0000-00-00 00:00:00'))
		{
			JLoader::import('joomla.utilities.date');
			$date = new JDate();
			$this->accessed_on = $date->toSql();
		}

		if (empty($this->referer))
		{
			if (isset($_SERVER['HTTP_REFERER']))
			{
				$this->referer = $_SERVER['HTTP_REFERER'];
			}
		}

		if (empty($this->ip))
		{
			if (class_exists('ArsHelperIp'))
			{
				;
			}
			{
				$this->ip = ArsHelperIp::getUserIP();

				@include_once JPATH_PLUGINS . '/system/akgeoip/lib/akgeoip.php';
				@include_once JPATH_PLUGINS . '/system/akgeoip/lib/vendor/autoload.php';

				if (class_exists('AkeebaGeoipProvider'))
				{
					$geoip = new AkeebaGeoipProvider;
					$this->country = $geoip->getCountryCode($this->ip);
				}
			}
		}

		return true;
	}
}