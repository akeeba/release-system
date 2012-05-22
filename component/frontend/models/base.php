<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelBaseFE extends JModel
{
	var $itemList = null;
	var $item = null;
	var $lists = null;
	var $pagination = null;

	/**
	 * Filters a list
	 * @param array $source The source list
	 * @return array The filtered list
	 */
	protected function filterList($source)
	{
		static $user_access = null;
		static $myGroups = null;

		// Initialise filtered list
		$list = array();

		// Check for empty source lists
		if(!is_array($source)) return $list;
		if(empty($source)) return $list;

		// Load Filtering
		$groupModel = JModel::getInstance('Filtering','ArsModel');

		// Cache user access and groups
		if(is_null($user_access) || is_null($myGroups))
		{
			// Do we have a dlid in the query?
			$dlid = JRequest::getCmd('dlid',null);
			if(strlen($dlid) > 32) $dlid = substr($dlid,0,32);
			
			$credentials = array();
			$credentials['username'] = JRequest::getVar('username', '', 'get', 'username');
			$credentials['password'] = JRequest::getString('password', '', 'get', JREQUEST_ALLOWRAW);
			if(!empty($dlid)) {
				// AUTHENTICATE AGAINST DLID
				$db = $this->getDBO();
				$query = 'SELECT `id`, md5(concat(`id`,`username`,`password`)) AS `dlid` FROM `#__users` WHERE md5(concat(`id`,`username`,`password`)) = '.
					$db->Quote($dlid);
				$db->setQuery($query);
				$user_id = $db->loadResult();

				if(empty($user_id) || ((int)$user_id <= 0) ) {
					$user = JFactory::getUser();
				} else {
					$user = JFactory::getUser($user_id);

					/*
					jimport( 'joomla.user.authentication');
					$app = JFactory::getApplication();
					$authenticate = JAuthentication::getInstance();
					$response = new JAuthenticationResponse();
					$response->status = JAUTHENTICATE_STATUS_SUCCESS;
					$response->type = 'joomla';
					$response->username = $user->username;
					$response->email = $user->email;
					$response->fullname = $user->name;

					JPluginHelper::importPlugin('user');
					$options = array();
					jimport('joomla.user.helper');
					$results = $app->triggerEvent('onLoginUser', array((array)$response, $options));
					$user = JFactory::getUser($user_id);
					$parameters['username']	= $user->get('username');
					$parameters['id']		= $user->get('id');
					*/
					//$results = $app->triggerEvent('onLogoutUser', array($parameters, $options));
				}
			} elseif( !empty($credentials['username']) && !empty($credentials['password']) ) {
				// AUTHENTICATE AGAINST USERNAME/PASSWORD PAIR IN QUERY

				jimport( 'joomla.user.authentication');
				$app = JFactory::getApplication();
				$options = array('remember' => false);
				$authenticate = JAuthentication::getInstance();
				$response	  = $authenticate->authenticate($credentials, $options);
				if ($response->status == JAUTHENTICATE_STATUS_SUCCESS) {
					JPluginHelper::importPlugin('user');
					$results = $app->triggerEvent('onLoginUser', array((array)$response, $options));
					if(version_compare(JVERSION,'1.6.0','ge')) {
						jimport('joomla.user.helper');
						$userid = JUserHelper::getUserId($response->username);
						$user = JFactory::getUser($userid);
					} else {
						$user = JFactory::getUser();
					}
					$parameters['username']	= $user->get('username');
					$parameters['id']		= $user->get('id');
					//$results = $app->triggerEvent('onLogoutUser', array($parameters, $options));
				} else {
					$user = JFactory::getUser();
				}
			} else {
				// USE ALREADY LOGGED IN USER (OR GUEST ACCOUNT)
				$user = JFactory::getUser();
			}

			// Get user info
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$user_access = $user->getAuthorisedViewLevels();
			} else {
				$user_access = 0;
				switch($user->gid) {
					case 18:
						$user_access = 1;
						break;
					case 19:
					case 20:
					case 21:
						$user_access = 2;
						break;
					case 23:
					case 24:
					case 25:
						$user_access = 3;
						break;
				}
			}

			// Get subscription groups of current user
			if(!ArsModelFiltering::hasSubscriptionsExtension()) {
				$mygroups = array();
			} else {
				$mygroups = ArsModelFiltering::getUserGroups($user->id);
			}
		}

		// Do the real filtering
		foreach($source as $s)
		{
			// Filter by access level
			if(!is_array($user_access)) {
				// Joomla! 1.5
				if($s->access > $user_access) continue;
			} else {
				if( !in_array($s->access, $user_access) ) continue;
			}

			// Filter by subscription group
			if(!empty($s->groups))
			{
				// Category defines subscriptions groups, user belongs to none, do
				// not display anything.
				if(empty($mygroups)) continue;

				// Check if any of the category's subscriptions groups are in the
				// list of groups the user belongs to
				$groups = explode(',', $s->groups);
				$inGroups = false;
				if(!empty($groups)) foreach($groups as $group)
				{
					if(in_array($group, $mygroups)) $inGroups = true;
				}
				else
				{
					$inGroups = true;
				}
				if(!$inGroups) continue;
			}

			$list[] = $s;
		}

		return $list;
	}
}