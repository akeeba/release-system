<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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

		// Short-circuit if AMBRA.subs is not present
		$groupModel = JModel::getInstance('Ambra','ArsModel');
		if(!ArsModelAmbra::hasAMBRA()) return $source;

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
				$query = 'SELECT `id` FROM `#__ars_view_dlid` WHERE `dlid` = '.
					$db->Quote($dlid);
				$db->setQuery();
				$user_id = $db->loadResult();

				if(empty($user_id) || ((int)$user_id <= 0) ) {
					$user = JFactory::getUser();
				} else {
					$user = JFactory::getUser($user_id);
				}
			} elseif( !empty($credentials['username']) && !empty($credentials['password']) ) {
				// AUTHENTICATE AGAINST USERNAME/PASSWORD PAIR IN QUERY
				jimport( 'joomla.user.authentication');
				$authenticate = & JAuthentication::getInstance();
				$response	  = $authenticate->authenticate($credentials, $options);
				if ($response->status === JAUTHENTICATE_STATUS_SUCCESS) {
					jimport('joomla.user.helper');
					$userid = (int)JUserHelper::getUserId($credentials['username']);
					$user = JFactory::getUser($userid);
				} else {
					$user = JFactory::getUser();
				}
			} else {
				// USE ALREADY LOGGED IN USER (OR GUEST ACCOUNT)
				$user = JFactory::getUser();
			}

			// Get user info
			$user_access = $user->aid;

			// Get AMBRA groups of current user
			$mygroups = $groupModel->getUserGroups($user->id);
		}

		// Do the real filtering
		foreach($source as $s)
		{
			// Filter by access level
			if($s->access > $user_access) continue;

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