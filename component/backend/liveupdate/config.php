<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
	var $_updateURL				= 'http://cdn.akeebabackup.com/updates/ars.ini';
	var $_requiresAuthorization	= false;
	var $_versionStrategy		= 'different';
	var $_storageAdapter		= 'component';
	var $_storageConfig			= array(
		'extensionName'	=> 'com_ars',
		'key'			=> 'liveupdate'
	);

	public function __construct() {
		JLoader::import('joomla.filesystem.file');
		$isPro = defined('ARS_PRO') ? (ARS_PRO == 1) : false;

		// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
		JLoader::import('joomla.html.parameter');
		JLoader::import('joomla.application.component.helper');
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type').' = '.$db->quote('component'))
			->where($db->quoteName('element').' = '.$db->quote('com_ars'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$params->loadString($rawparams, 'JSON');
		} else {
			$params->loadJSON($rawparams);
		}

		// Determine the appropriate update URL based on whether we're on Core or Professional edition
		if($isPro)
		{
			$this->_updateURL = 'http://cdn.akeebabackup.com/updates/arspro.ini';
			$this->_extensionTitle = 'Akeeba Release System Professional';
		}
		else
		{
			$this->_updateURL = 'http://cdn.akeebabackup.com/updates/ars.ini';
			$this->_extensionTitle = defined('AKEEBASUBS_PRO') ? 'Akeeba Release System Core' : 'Akeeba Release System';
		}

		// Dev releases use the "newest" strategy
		if(substr($this->_currentVersion,1,2) == 'ev') {
			$this->_versionStrategy = 'newest';
		} else {
			$this->_versionStrategy = 'vcompare';
		}

		// Get the minimum stability level for updates
		$this->_minStability = $params->get('minstability', 'stable');

		// Do we need authorized URLs?
		$this->_requiresAuthorization = $isPro;

		// Should I use our private CA store?
		if(@file_exists(dirname(__FILE__).'/../assets/cacert.pem')) {
			$this->_cacerts = dirname(__FILE__).'/../assets/cacert.pem';
		}

		parent::__construct();
	}
}