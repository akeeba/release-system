<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF40\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\String\StringHelper;

defined('_JEXEC') or die();

/**
 * Temporary workaround due to Joomla 4 extension installer bugs
 *
 * Modern Joomla 4 plugins do NOT have a plugin entry point file with a class following the plgTypeName convention.
 * Instead, they have a Joomla\Plugin\PLUGIN_TYPE\PLUGIN_NAME\Extension\PLUGIN_NAME class which is instantiated by the
 * services provider (plugins/PLUGIN_TYPE/services/provider.php) after registering the PSR-4 autoloader for the
 * Joomla\Plugin\PLUGIN_TYPE\PLUGIN_NAME class prefix.
 *
 * However, this means that the XML manifest can not have a `<filename plugin="PLUGIN_NAME">PLUGIN_NAME.php</filename>`
 * line any more since the PLUGIN_NAME.php file no longer exists. For historical reasons traced back to Mambo back in
 * 2003 that line is, however, how the plugin extension installation adapter determines the "element" (extension name).
 * Due to what seems to be a bug in that class, the alternative of defining an `<element>` tag in the manifest seems to
 * be ignored. Therefore the plugin becomes impossible to install without this line. As a result we need to include this
 * line.
 *
 * However, if we include this line we now need the PLUGIN_NAME.php file to be present. This is not that big of a deal
 * because Joomla 4 completely IGNORES this file. That's right, it doesn't even bother loading it! This file is
 * considered "legacy" but our plugin already has a services provider. The services provider takes priority and does
 * return an instance of CMSPlugin, therefore the legacy code never kicks in.
 *
 * So, if we just create an empty legacy plugin class in this file we're covered.
 *
 * That is why you see this weird empty plugin file here. No, we're not insane, we did not forget to implement the
 * plugin, we are just working around some problems in Joomla 4.
 */
class plgContentArslatest extends CMSPlugin
{
}
