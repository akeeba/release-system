<?php
/**
 * @package AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>=')) return;

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED') || !class_exists('FOFLess', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_ars', true)) return;

class plgContentArsjed extends JPlugin
{
	public function onAfterInitialise()
	{
		$app = JFactory::getApplication();

		$installat		= base64_decode($app->input->get('installat', null, 'base64'));
		$installapp 	= $app->input->get('installapp', null, 'int');

		if (!empty($installapp) && !empty($installat))
		{
			$session = JFactory::getSession();
			$session->set('installat', $installat, 'arsjed');
			$session->set('installapp', $installapp, 'arsjed');
		}
	}
}
