<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

/**
 * Composite integration with AMBRA Subscriptions and Akeeba Subscriptions.
 * It will favour Akeeba Subscriptions integration over AMBRA if both
 * extensions are installed.
 * @author nicholas
 *
 */
class ArsModelAmbra extends JModel
{
	public static $hasSubsExtension = null;
	
	public static $subsExtensionType = null;
	
	/**
	 * Checks if AMBRA.subs is installed
	 */
	static function hasAMBRA()
	{
		static $hasAmbra = null;

		if(is_null($hasAmbra)) {
			jimport('joomla.filesystem.folder');
			$hasAmbra = JFolder::exists(JPATH_ROOT.DS.'components/com_ambrasubs');
			
			if($hasAmbra) {
				jimport('joomla.application.component.helper');
				$hasAmbra = JComponentHelper::getComponent( 'com_ambrasubs', true )->enabled;
			}
		}

		return $hasAmbra;
	}
	
	/**
	 * Checks if Akeeba Subscriptions is installed
	 */
	static function hasAkeebaSubs()
	{
		static $hasAkeebaSubs = null;

		if(is_null($hasAkeebaSubs)) {
			jimport('joomla.filesystem.folder');
			$hasAkeebaSubs = JFolder::exists(JPATH_ROOT.DS.'components/com_akeebasubs');
			
			if($hasAkeebaSubs) {
				jimport('joomla.application.component.helper');
				$hasAkeebaSubs = JComponentHelper::getComponent( 'com_akeebasubs', true )->enabled;
			}
		}

		return $hasAkeebaSubs;
	}
	
	/**
	 * Checks if AMBRA.subs is installed
	 */
	static function hasSubscriptionsExtension()
	{
		if(is_null(self::$hasSubsExtension)) {
			if( self::hasAkeebaSubs() ) {
				self::$hasSubsExtension = true;
				self::$subsExtensionType = 'akeeba';
			} elseif( self::hasAMBRA() ) {
				self::$hasSubsExtension = true;
				self::$subsExtensionType = 'ambra';
			} else {
				self::$hasSubsExtension = false;
				self::$subsExtensionType = null;
			}
		}
		
		return self::$hasSubsExtension;
	}
	
	/**
	 * Returns the subscriptions extension installed and integrated on the site,
	 * favoring Akeeba Subscriptions over AMBRA Subscriptions if both are installed.
	 */
	static function getExtensionType() {
		if(is_null(self::$hasSubsExtension)) {
			self::hasSubscriptionsExtension();
		}
		
		return self::$subsExtensionType;
	}

	/**
	 * Returns a list of subscription groups / levels
	 */
	static function getGroups() {
		switch(self::getExtensionType()) {
			case 'ambra':
				return self::getAmbraGroups();
				break;
				
			case 'akeeba':
				return self::getAkeebaGroups();
				break;
				
			default:
				return array();
		}
	}
	
	/**
	 * Returns a list of all published AMBRA.subs groups (subscription types)
	 * @staticvar array $groupsList
	 * @return array A list of objects: {id, title}
	 */
	static function getAmbraGroups()
	{
		static $groupsList = null;
		
		if(!is_array($groupsList))
		{
			if(self::hasAMBRA())
			{
				$db = JFactory::getDBO();
				$query = "SELECT `id`,`title` FROM `#__ambrasubs_types` WHERE `published` = 1";
				$db->setQuery($query);
				$groupsList = $db->loadObjectList();
				if(empty($groupsList)) $groupsList = array();
			}
			else
			{
				$groupsList = array();
			}
		}
		
		return $groupsList;
	}
	
	/**
	 * Returns a list of all subscription levels on the site's Akeeba Subscriptions installation
	 */
	static function getAkeebaGroups()
	{
		static $theList = null;
		
		if(is_null($theList)) {
			$theList = array();
			
			$rawList = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->getList();
			
			if(!empty($rawList)) foreach($rawList as $item) {
				$theList[] = (object)array(
					'id'		=> $item->id,
					'title'		=> $item->title
				);
			}
		}
		
		return $theList;
	}

	/**
	 * Returns a list of subscription groups/levels the current user belongs to
	 * @param int $user_id User ID to check. Ommit to use current logged-in user
	 * @return array Array of the group the user belongs to (integers)
	 */
	static function getUserGroups($user_id = null)
	{
		if(!self::hasSubscriptionsExtension()) return array();
		
		if(is_null($user_id))
		{
			$user = JFactory::getUser();
			$user_id = $user->id;
		}
		
		switch(self::getExtensionType()) {
			case 'akeeba':
				return self::getAkeebaUserGroups($user_id);
				break;
				
			case 'ambra':
				return self::getAMBRAUserGroups($user_id);
				break;
				
			default:
				return array();
				break;
		}
	}
	
	/**
	 * Returns a list of AMBRA.subs groups the current user belongs to
	 * @param int $user_id User ID to check. Ommit to use current logged-in user
	 * @return array Array of the group the user belongs to (integers)
	 */
	static function getAMBRAUserGroups($user_id = null)
	{
		if(!self::hasAMBRA()) return array();

		$db = JFactory::getDBO();
		$query = <<<ENDSQL
SELECT
	`typeid`
FROM
	`#__ambrasubs_users2types`
WHERE
	`userid` = $user_id
	AND `status` = 1
	AND `expires_datetime` >= CURRENT_TIMESTAMP
ENDSQL;
		$db->setQuery($query);
		$list = $db->loadResultArray();
		if(empty($list)) $list = array();

		return $list;
	}
	
	static function getAkeebaUserGroups($user_id = null)
	{
		if(!self::hasAkeebaSubs()) return array();
		
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		
		$rawList = KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
			->enabled(1)
			->user_id($user_id)
			->limit(0)
			->getList();
			
		$theList = array();
		
		foreach($rawList as $item) {
			$theList[] = $item->akeebasubs_level_id;
		}
		
		return array_unique($theList);
	}

}