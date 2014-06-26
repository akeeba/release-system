<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
defined('_JEXEC') or die();

if (!class_exists('ArsHelperFilter'))
{
	@include_once JPATH_SITE . '/components/com_ars/helpers/filter.php';
}

$dlid = class_exists('ArsHelperFilter') ? ArsHelperFilter::myDownloadID() : '';

if (!is_null($dlid))
{
	require JModuleHelper::getLayoutPath('mod_arsdlid', $params->get('layout', 'default'));
}