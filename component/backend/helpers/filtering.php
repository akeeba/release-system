<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

JLoader::import('joomla.application.component.model');

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
			JLoader::import('joomla.filesystem.folder');
			$hasAkeebaSubs = JFolder::exists(JPATH_ROOT.'/components/com_akeebasubs');

			if($hasAkeebaSubs) {
				JLoader::import('joomla.application.component.helper');
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

			JLoader::import('joomla.filesystem.folder');
			JLoader::import('joomla.filesystem.file');

			$nooku = false;
			$rawList = F0FModel::getTmpInstance('Levels','AkeebasubsModel')
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
		static $userGroups = array();

		if(!self::hasSubscriptionsExtension()) return array();

		if(is_null($user_id))
		{
			$user = JFactory::getUser();
			$user_id = $user->id;
		}

		if (!array_key_exists($user_id, $userGroups))
		{
			switch(self::getExtensionType()) {
				case 'akeeba':
					$userGroups[$user_id] = self::getAkeebaUserGroups($user_id);
					break;

				case 'ambra':
					$userGroups[$user_id] = self::getAMBRAUserGroups($user_id);
					break;

				case 'payplans':
					$status = PayplansStatus::SUBSCRIPTION_ACTIVE;
					$userGroups[$user_id] = PayplansApi::getUser($user_id)->getSubscriptions($status);
					break;

				default:
					return array();
					break;
			}
		}

		return $userGroups[$user_id];
	}

	static function getAkeebaUserGroups($user_id = null)
	{
		if(!self::hasAkeebaSubs()) return array();

		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();

		JLoader::import('joomla.filesystem.folder');
		JLoader::import('joomla.filesystem.file');
		$rawList = F0FModel::getTmpInstance('Subscriptions','AkeebasubsModel',array('table'=>'subscriptions','input'=>array('option'=>'com_akeebasubs')))
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