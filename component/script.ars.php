<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ars.php 123 2011-04-13 07:47:16Z nikosdion $
 */

class Com_ArsInstallerScript {
	function postflight($type, $parent) {
		define('_AKEEBA_HACK', 1);
		require_once('install.ars.php');
	}
}