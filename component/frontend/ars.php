<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Check for PHP4
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}

// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>='))
{
	return JError::raise(E_ERROR, 500, 'PHP versions 4.x, 5.0, 5.1 and 5.2.0-5.2.6 are no longer supported by Akeeba Release System.','The version of PHP used on your site is obsolete and contains known security vulenrabilities. Moreover, it is missing features required by Akeeba Release System to work properly or at all. Please ask your host to upgrade your server to the latest PHP 5.3 release. Thank you!');
}

// Load F0F
include_once JPATH_LIBRARIES.'/f0f/include.php';
if(!defined('F0F_INCLUDED') || !class_exists('F0FForm', true))
{
	JError::raiseError ('500', 'Your Akeeba Release System installation is broken; please re-install. Alternatively, extract the installation archive and copy the fof directory inside your site\'s libraries directory.');
}

F0FDispatcher::getTmpInstance('com_ars')->dispatch();