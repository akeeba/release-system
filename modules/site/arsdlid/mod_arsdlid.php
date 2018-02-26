<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
defined('_JEXEC') or die();

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');

if (!JComponentHelper::isEnabled('com_ars'))
{
	return;
}

if (!class_exists('Akeeba\\ReleaseSystem\\Site\\Helper\\Filter'))
{
	// This has the side-effect of initialising our auto-loader
	\FOF30\Container\Container::getInstance('com_ars');
}

$dlid = \Akeeba\ReleaseSystem\Site\Helper\Filter::myDownloadID();

if (!is_null($dlid))
{
	require JModuleHelper::getLayoutPath('mod_arsdlid', $params->get('layout', 'default'));
}
