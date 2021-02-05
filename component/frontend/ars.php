<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

define('AKEEBA_COMMON_WRONGPHP', 1);
$minPHPVersion         = '7.3.0';
$recommendedPHPVersion = '7.4';
$softwareName          = 'Akeeba Release System';
$silentResults         = true;

if (!require_once(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/ErrorPages/wrongphp.php'))
{
	echo 'Your PHP version is too old for this component.';

	return;
}

if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	throw new RuntimeException('FOF 4.0 is not installed', 500);
}

FOF40\Container\Container::getInstance('com_ars')->dispatcher->dispatch();
