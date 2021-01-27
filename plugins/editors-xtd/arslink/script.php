<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

// Load FOF if not already loaded
if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	throw new RuntimeException('This extension requires FOF 4.0.');
}

class plgEditorsxtdArslinkInstallerScript extends FOF40\InstallScript\Plugin
{
	/**
	 * The plugins's name, e.g. foobar (for plg_system_foobar)
	 *
	 * @var   string
	 */
	protected $pluginName = 'arslink';

	/**
	 * The plugins's folder, e.g. system (for plg_system_foobar)
	 *
	 * @var   string
	 */
	protected $pluginFolder = 'editors-xtd';

}
