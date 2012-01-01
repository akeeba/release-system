<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */
defined('_JEXEC') or die();

if (!class_exists('modDLID')) {
	class modDLID {
		public static function getDLID()
		{
			// Get the User ID
			$user = JFactory::getUser();
			$id = $user->id;
			
			// Fail if it's a guest
			if(empty($id)) return null;
			
			$db = JFactory::getDBO();
			$sql = 'SELECT md5(concat(`id`,`username`,`password`)) AS `dlid` FROM `#__users` WHERE `id` = '.$id;
			$db->setQuery($sql);
			return $db->loadResult();
		}
	}
}

$dlid = modDLID::getDLID();

if(!is_null($dlid)) {
	require JModuleHelper::getLayoutPath('mod_arsdlid', $params->get('layout', 'default'));
}