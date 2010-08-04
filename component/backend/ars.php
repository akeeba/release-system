<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.filesystem.file');

// Get the view and controller from the request, or set to default if they weren't set
JRequest::setVar('view', JRequest::getCmd('view','cpanel'));
JRequest::setVar('c', JRequest::getCmd('view','cpanel')); // Black magic: Get controller based on the selected view

// Merge the default translation with the current translation
$jlang =& JFactory::getLanguage();
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
$path = JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.$c.'.php';
$alt_path = JPATH_COMPONENT_ADMINISTRATOR.DS.'plugins'.DS.'controllers'.DS.$c.'.php';
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
	JFile::exists($path) or	JError::raiseError('500',JText::_('Unknown controller').' '.$c);
}

// Instanciate and execute the controller
jimport('joomla.utilities.string');
$c = 'ArsController'.ucfirst($c);
$controller = new $c();
$controller->execute(JRequest::getCmd('task','display'));

// Redirect
$controller->redirect();