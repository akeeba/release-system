<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use FOF40\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die();

if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
if (!ComponentHelper::isEnabled('com_ars'))
{
	return;
}

if (!class_exists('Akeeba\\ReleaseSystem\\Site\\Helper\\Filter'))
{
	// This has the side-effect of initialising our auto-loader
	Container::getInstance('com_ars');
}

$dlid = Filter::myDownloadID();

if (!is_null($dlid))
{
	require ModuleHelper::getLayoutPath('mod_arsdlid', $params->get('layout', 'default'));
}
