<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.filesystem.file');

// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
if(function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
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

// Get the view and controller from the request, or set to default if they weren't set
JRequest::setVar('view', JRequest::getCmd('view','browse'));
JRequest::setVar('c', JRequest::getCmd('view','browse')); // Black magic: Get controller based on the selected view

// Merge the default translation with the current translation
$jlang =& JFactory::getLanguage();
// Back-end translation
$jlang->load('com_ars', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_ars', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_ars', JPATH_ADMINISTRATOR, null, true);
// Front-end translation
$jlang->load('com_ars', JPATH_SITE, 'en-GB', true);
$jlang->load('com_ars', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_ars', JPATH_SITE, null, true);

// Tell JModel to look for models and tables in the back-end component directory
jimport('joomla.application.component.model');
JModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'models');
JModel::addTablePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');

// Load the routeing helper
require_once dirname(__FILE__).DS.'helpers'.DS.'router.php';

// Load the appropriate controller
$c = JRequest::getCmd('c','cpanel');
$path = JPATH_COMPONENT.DS.'controllers'.DS.$c.'.php';
$alt_path = JPATH_COMPONENT.DS.'plugins'.DS.'controllers'.DS.$c.'.php';
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
	$path = JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.'default.php';
	if(!JFile::exists($path)) {
		JError::raiseError('500',JText::_('Unknown controller').' '.$c);
	}
	require_once $path;
}

// Instanciate and execute the controller
jimport('joomla.utilities.string');
$c = 'ArsController'.ucfirst($c);
$controller = new $c();
$controller->execute(JRequest::getCmd('task','display'));

// Redirect
$controller->redirect();