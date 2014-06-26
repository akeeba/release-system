<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class plgArsTainting extends JPlugin
{
	public function onARSBeforeSendFile($object)
	{
		$filename = strtolower($object['filename']);
		if (substr($filename, -4) == '.zip')
		{
			$object['filesize'] += 11;

			return $object;
		}
		else
		{
			$ret = null;

			return $ret;
		}
	}

	public function onARSAfterSendFile($object)
	{
		$filename = strtolower($object['filename']);
		if (substr($filename, -4) == '.zip')
		{
			$user = JFactory::getUser();
			$id = $user->id;
			$ret = 'PK777' . sprintf('%06u', $id);
		}
		else
		{
			$ret = null;
		}

		return $ret;
	}
}