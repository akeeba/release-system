<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

/**
 * Configuration class for your extension's updates. Override to your liking.
 */
class LiveUpdateConfig extends LiveUpdateAbstractConfig
{
	var $_extensionName			= 'com_ars';
	var $_extensionTitle		= 'Akeeba Release System';
	var $_updateURL				= 'https://www.akeebabackup.com/index.php?option=com_ars&view=update&format=ini&id=9';
	var $_requiresAuthorization	= false;
	var $_versionStrategy		= 'different';
	
	function __construct()
	{
		$this->_cacerts = dirname(__FILE__).'/../assets/cacert.pem';
		
		parent::__construct();
	}
}