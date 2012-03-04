<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
if( !version_compare(JVERSION, '1.6.0', 'ge') && function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	if(function_exists('error_reporting')) {
		$oldLevel = error_reporting(0);
	}
	$serverTimezone = @date_default_timezone_get();
	if(empty($serverTimezone) || !is_string($serverTimezone)) $serverTimezone = 'UTC';
	if(function_exists('error_reporting')) {
		error_reporting($oldLevel);
	}
	@date_default_timezone_set( $serverTimezone);
}

// Access check
if(version_compare(JVERSION, '1.6.0', 'ge')) {
	// Access check, Joomla! 1.6 style.
	$user = JFactory::getUser();
	if (!$user->authorise('core.manage', 'com_ars') && !$user->authorise('core.admin', 'com_ars')) {
		return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
	}
}

// Handle Live Update requests
require_once JPATH_COMPONENT_ADMINISTRATOR.'/liveupdate/liveupdate.php';
if(JRequest::getCmd('view','') == 'liveupdate') {
	LiveUpdate::handleRequest();
	return;
}

jimport('joomla.filesystem.file');

// Get the view and controller from the request, or set to default if they weren't set
JRequest::setVar('view', JRequest::getCmd('view','cpanel'));
JRequest::setVar('c', JRequest::getCmd('view','cpanel')); // Black magic: Get controller based on the selected view

// Merge the default translation with the current translation
$jlang = JFactory::getLanguage();
// Front-end translation
$jlang->load('com_ars', JPATH_SITE, 'en-GB', true);
$jlang->load('com_ars', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_ars', JPATH_SITE, null, true);
// Back-end translation
$jlang->load('com_ars', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_ars', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_ars', JPATH_ADMINISTRATOR, null, true);

// Load the appropriate controller
$c = JRequest::getCmd('c','cpanel');
$path = JPATH_COMPONENT_ADMINISTRATOR.'/controllers/'.$c.'.php';
$alt_path = JPATH_COMPONENT_ADMINISTRATOR.'/plugins/controllers/'.$c.'.php';
if(JFile::exists($path))
{
	// The requested controller exists and there you load it...
	require_once($path);
}
elseif(JFile::exists($alt_path))
{
	require_once($alt_path);
}
else
{
	$c = 'Default';
	$path = JPATH_COMPONENT_ADMINISTRATOR.'/controllers/default.php';
	if(!JFile::exists($path)) {
		JError::raiseError('500',JText::_('Unknown controller').' '.$c);
	}
	require_once $path;
}

// Load the Amazon S3 support
define('AKEEBA_CACERT_PEM', JPATH_ADMINISTRATOR.'/components/com_ars/assets/cacert.pem');
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/amazons3.php';

// Instanciate and execute the controller
jimport('joomla.utilities.string');
$c = 'ArsController'.ucfirst($c);
$controller = new $c();
$controller->execute(JRequest::getCmd('task','display'));

// Redirect
$controller->redirect();