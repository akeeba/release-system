<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

/**
 * Fetches the update information from the server or the cache, depending on
 * whether the cache is fresh or not.
 */
class LiveUpdateFetch extends JObject
{
	private $cacheTTL = 24;
	
	private $storage = null;
	
	/**
	 * One-stop-shop function which fetches update information and tells you
	 * if there are updates available or not, or if updates are not supported.
	 * 
	 * @return int 0 = no updates, 1 = updates available, -1 = updates not supported, -2 = fetching updates crashes the server
	 */
	public function hasUpdates()
	{
		$updateInfo = $this->getUpdateInformation();
		
		if($updateInfo->stuck) return -2;
		
		if(!$updateInfo->supported) return -1;
		
		$config = LiveUpdateConfig::getInstance();
		$extInfo = $config->getExtensionInformation();
		
		switch($config->getVersionStrategy()) {
			case 'newest':
				jimport('joomla.utilities.date');
				if(empty($extInfo)) {
					$mine = new JDate('2000-01-01 00:00:00');
				} else {
					$mine = new JDate($extInfo['date']);
				}
				
				$theirs = new JDate($updateInfo->date);
				
				return ($theirs->toUnix() > $mine->toUnix()) ? 1 : 0;
				break;
			
			case 'vcompare':
				$mine = $extInfo['version'];
				if(empty($mine)) $mine = '0.0.0';
				$theirs = $updateInfo->version;
				if(empty($theirs)) $theirs = '0.0.0';
				
				return (version_compare($theirs, $mine, 'gt')) ? 1 : 0;
				break;
			
			case 'different':
				$mine = $extInfo['version'];
				if(empty($mine)) $mine = '0.0.0';
				$theirs = $updateInfo->version;
				if(empty($theirs)) $theirs = '0.0.0';
				
				return ($theirs != $mine) ? 1 : 0;
				break;
		}
	}
	
	/**
	 * Get the latest version (update) information, either from the cache or
	 * from the update server.
	 * 
	 * @param $force bool Set to true to force fetching fresh data from the server
	 * 
	 * @return stdClass The update information, in object format
	 */
	public function getUpdateInformation($force = false)
	{
		// Get the Live Update configuration
		$config = LiveUpdateConfig::getInstance();
		
		// Get an instance of the storage class
		$storageOptions = $config->getStorageAdapterPreferences();
		require_once dirname(__FILE__).'/storage/storage.php';
		$this->storage = LiveUpdateStorage::getInstance($storageOptions['adapter'], $storageOptions['config']);
		
		// Fetch information from the cache
		jimport('joomla.utilities.date');
		$jDefaultDate = new JDate('2000-01-01 00:00:00');
		$lastCheck = $this->storage->get('lastcheck', $jDefaultDate->toUnix());
		$cachedData = $this->storage->get('updatedata', null);
		
		if(empty($cachedData)) $lastCheck = $jDefaultDate->toUnix();
		
		// Check if the cache is at most $cacheTTL hours old
		$jNow = new JDate();
		$jLast = new JDate($lastCheck);
		$maxDifference = $this->cacheTTL * 3600;
		$difference = abs($jNow->toUnix() - $jLast->toUnix());
		if(!($force) && ($difference <= $maxDifference)) {
			// The cache is fresh enough; return cached data
			return $cachedData;
		} else {
			// The cache is stale; fetch new data, cache it and return it to the caller
			$data = $this->getUpdateData($force);
			$this->storage->set('lastcheck', $jNow->toUnix());
			$this->storage->set('updatedata', $data);
			$this->storage->save();
			return $data;
		}
	}
	
	/**
	 * Retrieves the update data from the server, unless previous runs indicate
	 * that the download process gets stuck and ends up in a WSOD.
	 * 
	 * @param bool $force Set to true to force fetching new data no matter if the process is marked as stuck
	 * @return stdClass
	 */
	private function getUpdateData($force = false)
	{
		$ret = array(
			'supported'		=> false,
			'stuck'			=> true,
			'version'		=> '',
			'date'			=> '',
			'stability'		=> '',
			'downloadURL'	=> ''
		);
		
		// If the process is marked as "stuck", we won't bother fetching data again; well,
		// unless you really force me to, by setting $force = true.
		if($this->storage->get('stuck',0) && !$force) return (object)$ret;
		
		$ret['stuck'] = false;
		
		// Does the server support cURL or URL fopen() wrappers?
		$method = '';
		if(function_exists('curl_exec')) {
			$method = 'curl';
		} else {
			if(function_exists('ini_get')) {
				if(ini_get('allow_url_fopen')) {
					$method = 'fopen';
				}
			}
		}
		
		if(empty($method)) {
			// Live Update is not supported on this server
			return (object)$ret;
		}
		
		// First we mark Live Updates as getting stuck. This way, if fetching the update
		// fails with a server error, reloading the page will not result to a White Screen
		// of Death again. Hey, Joomla! core team, are you listening? Some hosts PRETEND to
		// support cURL or URL fopen() wrappers but using them throws an immediate WSOD.
		$this->storage->set('stuck', 1);
		$this->storage->save(); 
		
		switch($method) {
			case 'curl':
				$rawData = $this->fetchCURL();
				break;
				
			case 'fopen':
				$rawData = $this->fetchFOPEN();
				break;
		}
		
		// Now that we have some data returned, let's unmark the process as being stuck ;)
		$this->storage->set('stuck', 0);
		$this->storage->save();
		
		// If we didn't get anything, assume Live Update is not supported (communication error)
		if(empty($rawData)) return (object)$ret;
		
		// TODO Detect the content type of the returned update stream. For now, I will pretend it's an INI file.
		
		$data = $this->parseINI($rawData);
		$ret['supported'] = true;
		
		return (object)array_merge($ret, $data);
	}
	
	/**
	 * Fetches update information from the server using cURL
	 * @return string The raw server data
	 */
	private function fetchCURL()
	{
		$config = LiveUpdateConfig::getInstance();
		$extInfo = $config->getExtensionInformation();
		$url = $extInfo['updateurl'];
		
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HEADER, 0);
		// Pretend we are Firefox, so that webservers play nice with us
		curl_setopt($process, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.14) Gecko/20110105 Firefox/3.6.14');
		curl_setopt($process, CURLOPT_ENCODING, 'gzip');
		curl_setopt($process, CURLOPT_TIMEOUT, 10);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		// The @ sign allows the next line to fail if open_basedir is set or if safe mode is enabled
		@curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt($process, CURLOPT_MAXREDIRS, 20);
		$inidata = curl_exec($process);
		curl_close($process);
		return $inidata;
	}
	
	/**
	 * Fetches update information from the server using file_get_contents, which internally
	 * uses URL fopen() wrappers.
	 * @return string The raw server data
	 */
	private function fetchFOPEN()
	{
		$config = LiveUpdateConfig::getInstance();
		$extInfo = $config->getExtensionInformation();
		$url = $extInfo['updateurl'];
		
		return @file_get_contents($urls);
	}
	
	/**
	 * Parses the raw INI data into an array of update information
	 * @param string $rawData The raw INI data
	 * @return array The parsed data
	 */
	private function parseINI($rawData)
	{
		$ret = array(
			'version'		=> '',
			'date'			=> '',
			'stability'		=> '',
			'downloadURL'	=> ''
		);
		
		// Get the magic string
		$magicPos = strpos($rawData, '; Live Update provision file');
		
		if($magicPos === false) {
			// That's not an INI file :(
			return $ret;
		}
		
		if($magicPos !== 0) {
			$rawData = substr($rawData, $magicPos);
		}
		
		require_once dirname(__FILE__).'/inihelper.php';
		$iniData = LiveUpdateINIHelper::parse_ini_file($rawData, false, true);
		
		$ret['version'] = $iniData['version'];
		$ret['date'] = $iniData['date'];
		$config = LiveUpdateConfig::getInstance();
		$auth = $config->getAuthorization();
		$ret['downloadURL'] = $iniData['link'] . (empty($auth) ? '' : '?'.$auth);
		if(array_key_exists('stability', $iniData)) {
			$stability = $iniData['stability'];
		} else {
			// Stability not defined; guesswork mode enabled
			$version = $ret['version'];
			if( preg_match('#^[0-9\.]*a[0-9\.]*#', $version) == 1 ) {
				$stability = 'alpha';
			} elseif( preg_match('#^[0-9\.]*b[0-9\.]*#', $version) == 1 ) {
				$stability = 'beta';
			} elseif( preg_match('#^[0-9\.]*rc[0-9\.]*#', $version) == 1 ) {
				$stability = 'rc';
			} elseif( preg_match('#^[0-9\.]*$#', $version) == 1 ) {
				$stability = 'stable';
			} else {
				$stability = 'svn';
			}
		}
		$ret['stability'] = $stability;

		return $ret;
	}
}