<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

jimport('joomla.application.component.model');

/**
 * Composite integration with AMBRA Subscriptions and Akeeba Subscriptions.
 * It will favour Akeeba Subscriptions integration over AMBRA if both
 * extensions are installed.
 * @author nicholas
 *
 */
class ArsHelperFiltering
{
	public static $hasSubsExtension = null;
	
	public static $subsExtensionType = null;
	
	/**
	 * Checks if Akeeba Subscriptions is installed
	 */
	static function hasAkeebaSubs()
	{
		static $hasAkeebaSubs = null;

		if(is_null($hasAkeebaSubs)) {
			jimport('joomla.filesystem.folder');
			$hasAkeebaSubs = JFolder::exists(JPATH_ROOT.'/components/com_akeebasubs');
			
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
			} elseif( defined('PAYPLANS_LOADED') ) {
				self::$hasSubsExtension = true;
				self::$subsExtensionType = 'payplans';
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
			case 'akeeba':
				return self::getAkeebaGroups();
				break;
			
			case 'payplans':
				return PayplansApi::getPlans();
				break;
				
			default:
				return array();
		}
	}
	
	/**
	 * Returns a list of all subscription levels on the site's Akeeba Subscriptions installation
	 */
	static function getAkeebaGroups()
	{
		static $theList = null;
		
		if(is_null($theList)) {
			$theList = array();
			
			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');
			
			$nooku = false;
			$rawList = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->enabled('')
				->limit(0)
				->limitstart(0)
				->getList();	
			
			if(!empty($rawList)) foreach($rawList as $item) {
				$theList[] = (object)array(
					'id'		=> $nooku ? $item->id : $item->akeebasubs_level_id,
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
				
			case 'payplans':
				$status = PayplansStatus::SUBSCRIPTION_ACTIVE;
				return PayplansApi::getUser($user_id)->getSubscriptions($status);
				break;  

			default:
				return array();
				break;
		}
	}
	
	static function getAkeebaUserGroups($user_id = null)
	{
		if(!self::hasAkeebaSubs()) return array();
		
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		$rawList = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel',array('table'=>'subscriptions','input'=>array('option'=>'com_akeebasubs')))
			->enabled(1)
			->user_id($user_id)
			->skipOnProcessList(1)
			->getList();
			
		$theList = array();
		
		foreach($rawList as $item) {
			$theList[] = $item->akeebasubs_level_id;
		}
		
		return array_unique($theList);
	}

}