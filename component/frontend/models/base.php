<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
	 * Filters a list by access level and AMBRA.subs groups
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

		// Load AMBRA integration
		$groupModel = JModel::getInstance('Ambra','ArsModel');

		// Cache user access and groups
		if(is_null($user_access) || is_null($myGroups))
		{
			// Do we have a dlid in the query?
			$dlid = JRequest::getCmd('dlid',null);
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
					$user = JFactory::getUser();
					$parameters['username']	= $user->get('username');
					$parameters['id']		= $user->get('id');
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
					$user = JFactory::getUser();
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
				$user_access = $user->aid;	
			}

			// Get AMBRA groups of current user
			if(!ArsModelAmbra::hasSubscriptionsExtension()) {
				$mygroups = array();
			} else {
				$mygroups = ArsModelAmbra::getUserGroups($user->id);
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

			// Filter by AMBRA.subs group
			if(!empty($s->groups))
			{
				// Category defines AMBRA.subs groups, user belongs to none, do
				// not display anything.
				if(empty($mygroups)) continue;

				// Check if any of the category's AMBRA.subs groups are in the
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