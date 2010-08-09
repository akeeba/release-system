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
			// Get user info
			$user = JFactory::getUser();
			$user_access = $user->aid;

			// Get AMBRA groups of current user
			$mygroups = $groupModel->getUserGroups();
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