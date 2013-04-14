<?php
/**
 * @package AkeebaRelink
 * @copyright Copyright ©2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU/GPL version 3 or, at your option, any later version
 */

if(stristr(php_uname(), 'windows')) {
	define('AKEEBA_RELINK_WINDOWS', 1);
}

function isLink($path)
{
	if(defined('AKEEBA_RELINK_WINDOWS')) {
		return file_exists($path);
	} else {
		return is_link($path);
	}
}

function symlink_dir($from, $to)
{
	if(is_dir($to))
	{
		if(AKEEBA_RELINK_WINDOWS)
		{
			$cmd = 'rmdir /s /q "'.$to.'"';
		}
		else
		{
			$cmd = 'rm -rf "'.$to.'"';
		}
		exec($cmd);
	}

	if(defined('AKEEBA_RELINK_WINDOWS')) {
		$cmd = 'mklink /D "'.$to.'" "'.$from.'"';
		exec($cmd);
	} else {
		@symlink($from, $to);
	}
}

function symlink_file($from, $to)
{
	if(file_exists($to))
	{
		if(AKEEBA_RELINK_WINDOWS)
		{
			$cmd = 'del /f /q "'.$to.'"';
		}
		else
		{
			$cmd = 'rm -f "'.$to.'"';
		}
		exec($cmd);
	}

	if(defined('AKEEBA_RELINK_WINDOWS')) {
		$cmd = 'mklink "'.$to.'" "'.$from.'"';
		exec($cmd);
	} else {
		@symlink($from, $to);
	}
}

function hardlink_file($from, $to)
{
	if(file_exists($to))
	{
		if(AKEEBA_RELINK_WINDOWS)
		{
			$cmd = 'del /f /q "'.$to.'"';
		}
		else
		{
			$cmd = 'rm -f "'.$to.'"';
		}
		exec($cmd);
	}

	if(defined('AKEEBA_RELINK_WINDOWS')) {
		$cmd = 'mklink /H "'.$to.'" "'.$from.'"';
		exec($cmd);
	} else {
		@link($from, $to);
	}
}

function realpath2($path)
 {
	if(defined('AKEEBA_RELINK_WINDOWS')) {
		return str_replace('/', '\\', $path);
	} else {
		return str_replace('\\', '/', $path);
	}
 }

class AkeebaRelink
{
	/** @var string The path to the sources */
	private $_root = null;

	/** @var string The path to the site's root */
	private $_siteRoot = null;

	/** @var string The version of the Joomla! site we're linking to */
	private $_joomlaVersion = '1.5';

	/** @var array Information about the modules */
	private $_modules = array();

	/** @var array Information about the plugins */
	private $_plugins = array();

	/** @var array Information about the component */
	private $_component = array();

	/**
	 * Public constructor. Initialises the class with the user-supplied information.
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		if(!array_key_exists('root', $config)) {
			$config['root'] = dirname(__FILE__);
		}
		if(!array_key_exists('site', $config)) {
			$config['site'] = '/Users/nicholas/Sites/jbeta';
		}

		$this->_root = $config['root'];
		$this->_siteRoot = $config['site'];

		// Detect the site's version
		$this->_detectJoomlaVersion();

		// Load information about the bundled extensions
		$this->_scanComponent();
		$this->_fetchModules();
		$this->_fetchPlugins();
	}

	/**
	 * Detect the exact version of a Joomla! site
	 */
	private function _detectJoomlaVersion()
	{
		define('_JEXEC', 1);
		define('JPATH_BASE', $this->_siteRoot);

		$file15 = $this->_siteRoot.'/libraries/joomla/version.php';
		$file16 = $this->_siteRoot.'/includes/version.php';
		$file25 = $this->_siteRoot.'/libraries/cms/version/version.php';

		if(@file_exists($file15)) {
			require_once $file15;
		} elseif(@file_exists($file16)) {
			require_once $file16;
		} elseif(@file_exists($file25)) {
			require_once $file25;
		}

		$v = new JVersion();
		$this->_joomlaVersion = $v->getShortVersion();
	}

	/**
	 * Gets the information for all included modules
	 */
	private function _fetchModules()
	{
		// Check if we have site/admin subdirectories, or just a bunch of modules
		$scanPath = $this->_root.'/modules';
		if( is_dir($scanPath.'/admin') || is_dir($scanPath.'/site') ) {
			$paths = array(
				$scanPath.'/admin',
				$scanPath.'/site',
			);
		} else {
			$paths = array(
				$scanPath
			);
		}

		// Iterate directories
		$this->_modules = array();
		foreach($paths as $path) {
			if( !is_dir($path) && !isLink($path) ) continue;
			foreach(new DirectoryIterator($path) as $fileInfo) {
				if($fileInfo->isDot()) continue;
				if(!$fileInfo->isDir()) continue;

				$modPath = $path.'/'.$fileInfo->getFilename();
				$info = $this->_scanModule($modPath);

				if(!is_array($info)) continue;
				if(!array_key_exists('module', $info)) continue;

				$this->_modules[] = $info;
			}
		}
	}

	/**
	 * Gets the information for all included plugins
	 */
	private function _fetchPlugins()
	{
		// Check if we have site/admin subdirectories, or just a bunch of modules
		$scanPath = $this->_root.'/plugins';
		if( is_dir($scanPath.'/system') || is_dir($scanPath.'/content') || is_dir($scanPath.'/user') ) {
			$paths = array();
			foreach(new DirectoryIterator($scanPath) as $fileInfo) {
				if($fileInfo->isDot()) continue;
				if(!$fileInfo->isDir()) continue;

				$paths[] = $scanPath.'/'.$fileInfo->getFilename();
			}
		} else {
			$paths = array(
				$scanPath
			);
		}

		// Iterate directories
		$this->_plugins = array();
		foreach($paths as $path) {
			if( !is_dir($path) && !isLink($path) ) continue;
			foreach(new DirectoryIterator($path) as $fileInfo) {
				if($fileInfo->isDot()) continue;
				if(!$fileInfo->isDir()) continue;

				$plgPath = $path.'/'.$fileInfo->getFilename();
				$info = $this->_scanPlugin($plgPath);

				if(!is_array($info)) continue;
				if(!array_key_exists('plugin', $info)) continue;

				$this->_plugins[] = $info;
			}
		}
	}

	/**
	 * Scans a module directory to fetch the extension information
	 *
	 * @param string $path
	 * @return array
	 */
	private function _scanModule($path)
	{
		// Find the XML files
		foreach(new DirectoryIterator($path) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if(!$fileInfo->isFile()) continue;
			$fname = $fileInfo->getFilename();
			if(substr($fname, -4) != '.xml') continue;

			$xmlDoc = new DOMDocument;
			$xmlDoc->load($path.'/'.$fname, LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NONET);

			$rootNodes = $xmlDoc->getElementsByTagname('install');
			$altRootNodes = $xmlDoc->getElementsByTagname('extension');
			if($altRootNodes->length >= 1) {
				unset($rootNodes);
				$rootNodes = $altRootNodes;
			}
			if($rootNodes->length < 1) {
				unset($xmlDoc);
				continue;
			}

			$root = $rootNodes->item(0);
			if(!$root->hasAttributes()) {
				unset($xmlDoc);
				continue;
			}
			if($root->getAttribute('type') != 'module') {
				unset($xmlDoc);
				continue;
			}

			$module = '';
			$files = $xmlDoc->getElementsByTagName('files')->item(0)->childNodes;
			foreach($files as $file) {
				if($file->hasAttributes()) {
					$module = $file->getAttribute('module');
				}
			}

			if($xmlDoc->getElementsByTagName('languages')->length < 1) {
				$langFolder = null;
				$langFiles = array();
			} else {
				$langTag = $xmlDoc->getElementsByTagName('languages')->item(0);
				$langFolder = $path.'/'.$langTag->getAttribute('folder');
				$langFiles = array();
				foreach($langTag->childNodes as $langFile) {
					if(!($langFile instanceof DOMElement)) continue;
					$tag = $langFile->getAttribute('tag');
					$lfPath = $langFolder.'/'.$langFile->textContent;
					$langFiles[$tag][] = $lfPath;
				}
			}

			if(empty($module)) {
				unset($xmlDoc);
				continue;
			}

			$ret = array(
				'module'	=> $module,
				'path'		=> $path,
				'client'	=> $root->getAttribute('client'),
				'langPath'	=> $langFolder,
				'langFiles'	=> $langFiles,
			);

			unset($xmlDoc);
			return $ret;
		}
	}

	/**
	 * Scans a plugin directory to fetch the extension information
	 *
	 * @param string $path
	 * @return array
	 */
	private function _scanPlugin($path)
	{
		// Find the XML files
		foreach(new DirectoryIterator($path) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if(!$fileInfo->isFile()) continue;
			$fname = $fileInfo->getFilename();
			if(substr($fname, -4) != '.xml') continue;

			$xmlDoc = new DOMDocument;
			$xmlDoc->load($path.'/'.$fname, LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NONET);

			$rootNodes = $xmlDoc->getElementsByTagname('install');
			$altRootNodes = $xmlDoc->getElementsByTagname('extension');
			if($altRootNodes->length >= 1) {
				unset($rootNodes);
				$rootNodes = $altRootNodes;
			}
			if($rootNodes->length < 1) {
				unset($xmlDoc);
				continue;
			}

			$root = $rootNodes->item(0);
			if(!$root->hasAttributes()) {
				unset($xmlDoc);
				continue;
			}
			if($root->getAttribute('type') != 'plugin') {
				unset($xmlDoc);
				continue;
			}

			$folder = $root->getAttribute('group');

			$plugin = '';
			$files = $xmlDoc->getElementsByTagName('files')->item(0)->childNodes;
			foreach($files as $file) {
				if($file->hasAttributes()) {
					$plugin = $file->getAttribute('plugin');
				}
			}

			if($xmlDoc->getElementsByTagName('languages')->length < 1) {
				$langFolder = null;
				$langFiles = array();
			} else {
				$langTag = $xmlDoc->getElementsByTagName('languages')->item(0);
				$langFolder = $path.'/'.$langTag->getAttribute('folder');
				$langFiles = array();
				foreach($langTag->childNodes as $langFile) {
					if(!($langFile instanceof DOMElement)) continue;
					$tag = $langFile->getAttribute('tag');
					$lfPath = $langFolder.'/'.$langFile->textContent;
					$langFiles[$tag][] = $lfPath;
				}
			}

			if(empty($plugin)) {
				unset($xmlDoc);
				continue;
			}

			$ret = array(
				'plugin'	=> $plugin,
				'folder'	=> $folder,
				'path'		=> $path,
				'langPath'	=> $langFolder,
				'langFiles'	=> $langFiles,
			);

			unset($xmlDoc);
			return $ret;
		}
	}

	/**
	 * Scan the component directory and get some useful info
	 * @return type
	 */
	private function _scanComponent()
	{
		$path = $this->_root.'/component';

		// Find the XML files
		foreach(new DirectoryIterator($path) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if(!$fileInfo->isFile()) continue;
			$fname = $fileInfo->getFilename();
			if(substr($fname, -4) != '.xml') continue;

			$xmlDoc = new DOMDocument;
			$xmlDoc->load($path.'/'.$fname, LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NONET);

			$rootNodes = $xmlDoc->getElementsByTagname('install');
			$altRootNodes = $xmlDoc->getElementsByTagname('extension');
			if($altRootNodes->length >= 1) {
				unset($rootNodes);
				$rootNodes = $altRootNodes;
			}
			if($rootNodes->length < 1) {
				unset($xmlDoc);
				continue;
			}

			$root = $rootNodes->item(0);
			if(!$root->hasAttributes()) {
				unset($xmlDoc);
				continue;
			}
			if($root->getAttribute('type') != 'component') {
				unset($xmlDoc);
				continue;
			}

			// Get the component name
			$component = strtolower($xmlDoc->getElementsByTagName('name')->item(0)->textContent);
			if(substr($component,0,4) != 'com_') $component = 'com_'.$component;

			// Get the <files> tags for front and back-end
			$siteFolder = $path;
			$allFilesTags = $xmlDoc->getElementsByTagName('files');
			$nodePath0 = $allFilesTags->item(0)->getNodePath();
			$nodePath1 = $allFilesTags->item(1)->getNodePath();
			if(in_array($nodePath0, array('/install/files','/extension/files'))) {
				$siteFilesTag = $allFilesTags->item(0);
				$adminFilesTag = $allFilesTags->item(1);
			} else {
				$siteFilesTag = $allFilesTags->item(1);
				$adminFilesTag = $allFilesTags->item(0);
			}

			// Get the site and admin folders
			if($siteFilesTag->hasAttribute('folder')) $siteFolder = $path.'/'.$siteFilesTag->getAttribute('folder');
			if($adminFilesTag->hasAttribute('folder')) $adminFolder = $path.'/'.$adminFilesTag->getAttribute('folder');

			// Get the media folder
			$mediaFolder = null;
			$allMediaTags = $xmlDoc->getElementsByTagName('media');
			if($allMediaTags->length >= 1) {
				$mediaFolder = $path.'/'.$allMediaTags->item(0)->getAttribute('folder');
			}

			// Do we have a CLI folder
			$cliFolder = $path.'/cli';
			if(!is_dir($cliFolder)) {
				$cliFolder = '';
			}

			// Get the <languages> tags for front and back-end
			$langFolderSite = $path;
			$langFolderAdmin = $path;
			$allLanguagesTags = $xmlDoc->getElementsByTagName('languages');
			$nodePath0 = $allLanguagesTags->item(0)->getNodePath();
			$nodePath1 = $allLanguagesTags->item(1)->getNodePath();

			if(in_array($nodePath0, array('/install/languages','/extension/languages'))) {
				$siteLanguagesTag = $allLanguagesTags->item(0);
				$adminLanguagesTag = $allLanguagesTags->item(1);
			} else {
				$siteLanguagesTag = $allLanguagesTags->item(1);
				$adminLanguagesTag = $allLanguagesTags->item(0);
			}

			// Get the site and admin language folders
			if($siteLanguagesTag->hasAttribute('folder')) $langFolderSite = $path.'/'.$siteLanguagesTag->getAttribute('folder');
			if($adminLanguagesTag->hasAttribute('folder')) $langFolderAdmin = $path.'/'.$adminLanguagesTag->getAttribute('folder');

			// Get the frontend languages
			$langFilesSite = array();
			if($siteLanguagesTag->hasChildNodes()) foreach($siteLanguagesTag->childNodes as $langFile)
			{
				if(!($langFile instanceof DOMElement)) continue;
				$tag = $langFile->getAttribute('tag');
				$langFilesSite[$tag][] = $langFolderSite.'/'.$langFile->textContent;
			}

			// Get the backend languages
			$langFilesAdmin = array();
			if($adminLanguagesTag->hasChildNodes()) foreach($adminLanguagesTag->childNodes as $langFile)
			{
				if(!($langFile instanceof DOMElement)) continue;
				$tag = $langFile->getAttribute('tag');
				$langFilesAdmin[$tag][] = $langFolderAdmin.'/'.$langFile->textContent;
			}

			if(empty($component)) {
				unset($xmlDoc);
				continue;
			}

			$this->_component = array(
				'component'		=> $component,
				'siteFolder'	=> $siteFolder,
				'adminFolder'	=> $adminFolder,
				'mediaFolder'	=> $mediaFolder,
				'cliFolder'		=> $cliFolder,
				'siteLangPath'	=> $langFolderSite,
				'siteLangFiles'	=> $langFilesSite,
				'adminLangPath'	=> $langFolderAdmin,
				'adminLangFiles'=> $langFilesAdmin,
			);

			unset($xmlDoc);
			return;
		}
	}

	/**
	 * Maps the folders and files for the component
	 * @return array
	 */
	private function _mapComponent()
	{
		$files = array();
		// Frontend and backend directories
		$dirs = array(
			$this->_component['siteFolder'] =>
				$this->_siteRoot.'/components/'.$this->_component['component'],
			$this->_component['adminFolder'] =>
				$this->_siteRoot.'/administrator/components/'.$this->_component['component'],
		);
		// Media directory
		if($this->_component['mediaFolder']) {
			$dirs[$this->_component['mediaFolder']] =
				$this->_siteRoot.'/media/'.$this->_component['component'];
		}
		// CLI files
		if($this->_component['cliFolder']) {
			foreach(new DirectoryIterator($this->_component['cliFolder']) as $fileInfo) {
				if($fileInfo->isDot()) continue;
				if(!$fileInfo->isFile()) continue;
				$fname = $fileInfo->getFileName();
				if(substr($fname, -4) != '.php') continue;

				$files[$this->_component['cliFolder'].'/'.$fname] =
					$this->_siteRoot.'/cli/'.$fname;
			}
		}

		// Front-end language files
		$basePath = $this->_siteRoot.'/language/';
		if(!empty($this->_component['siteLangFiles'])) {
			foreach($this->_component['siteLangFiles'] as $tag => $lfiles) {
				$path = $basePath.$tag.'/';
				foreach($lfiles as $lfile) {
					$files[$lfile] = $path.basename($lfile);
				}
			}
		}

		// Back-end language files
		$basePath = $this->_siteRoot.'/administrator/language/';
		if(!empty($this->_component['adminLangFiles'])) {
			foreach($this->_component['adminLangFiles'] as $tag => $lfiles) {
				$path = $basePath.$tag.'/';
				foreach($lfiles as $lfile) {
					$files[$lfile] = $path.basename($lfile);
				}
			}
		}

		return array(
			'dirs'	=> $dirs,
			'files'	=> $files,
		);
	}

	private function _mapModule($module)
	{
		$files = array();
		$dirs = array();

		$basePath = $this->_siteRoot.'/';
		if($module['client'] != 'site') $basePath .= 'administrator/';
		$basePath .= 'modules/'.$module['module'];

		$dirs[$module['path']] = $basePath;

		// Language files
		if($module['client'] != 'site') {
			$basePath = $this->_siteRoot.'/administrator/language/';
		} else {
			$basePath = $this->_siteRoot.'/language/';
		}
		if(!empty($module['langFiles'])) {
			foreach($module['langFiles'] as $tag => $lfiles) {
				$path = $basePath.$tag.'/';
				foreach($lfiles as $lfile) {
					$files[$lfile] = $path.basename($lfile);
				}
			}
		}

		return array(
			'dirs'	=> $dirs,
			'files'	=> $files,
		);
	}

	private function _mapPlugin($plugin)
	{
		$files = array();
		$dirs = array();

		if(version_compare($this->_joomlaVersion, '1.6.0', 'ge')) {
			// Joomla! 1.6 or later -- just link one folder
			$basePath = $this->_siteRoot.'/plugins/'.$plugin['folder'].'/'.$plugin['plugin'];
			$dirs[$plugin['path']] = $basePath;
		} else {
			// Joomla! 1.5 -- we've got to scan for files and directories
			$basePath = $this->_siteRoot.'/plugins/'.$plugin['folder'].'/';
			foreach(new DirectoryIterator($plugin['path']) as $fileInfo) {
				if($fileInfo->isDot()) continue;
				$fname = $fileInfo->getFileName();
				if($fileInfo->isDir()) {
					$dirs[$plugin['path'].'/'.$fname] = $basePath.$fname;
				} elseif($fileInfo->isFile()) {
					$dirs[$plugin['path'].'/'.$fname] = $basePath.$fname;
				}
			}
		}
		// Language files
		$basePath = $this->_siteRoot.'/administrator/language/';
		if(!empty($plugin['langFiles'])) {
			foreach($plugin['langFiles'] as $tag => $lfiles) {
				$path = $basePath.$tag.'/';
				foreach($lfiles as $lfile) {
					$files[$lfile] = $path.basename($lfile);
				}
			}
		}

		return array(
			'dirs'	=> $dirs,
			'files'	=> $files,
		);
	}

	/**
	 * Unlinks the component
	 */
	public function unlinkComponent()
	{
		echo "Unlinking component ".$this->_component['component']."\n";

		$map = $this->_mapComponent();
		extract($map);

		$dirs = array_values($dirs);
		$files = array_values($files);

		$this->_unlinkDirectories($dirs);
		if(!empty($files)) $this->_unlinkFiles($files);
	}

	/**
	 * Unlinks the modules
	 */
	public function unlinkModules()
	{
		if(empty($this->_modules)) return;
		foreach($this->_modules as $module)
		{
			echo "Unlinking module ".$module['module'].' ('.$module['client'].")\n";

			$map = $this->_mapModule($module);
			extract($map);

			$dirs = array_values($dirs);
			$files = array_values($files);

			$this->_unlinkDirectories($dirs);
			if(!empty($files)) $this->_unlinkFiles($files);
		}
	}

	/**
	 * Unlinks the plugins
	 */
	public function unlinkPlugins()
	{
		if(empty($this->_plugins)) return;
		foreach($this->_plugins as $plugin)
		{
			echo "Unlinking plugin ".$plugin['plugin'].' ('.$plugin['folder'].")\n";

			$map = $this->_mapPlugin($plugin);
			extract($map);

			$dirs = array_values($dirs);
			$files = array_values($files);

			$this->_unlinkDirectories($dirs);
			if(!empty($files)) $this->_unlinkFiles($files);
		}
	}

	/**
	 * Relinks the component
	 */
	public function linkComponent()
	{
		echo "Linking component ".$this->_component['component']."\n";

		$map = $this->_mapComponent();
		extract($map);

		foreach($dirs as $from => $to)
		{
			symlink_dir(realpath2($from), realpath2($to));
		}

		foreach($files as $from => $to)
		{
			symlink_file(realpath2($from), realpath2($to));
		}
	}

	/**
	 * Relinks the modules
	 */
	public function linkModules()
	{
		if(empty($this->_modules)) return;
		foreach($this->_modules as $module)
		{
			echo "Linking module ".$module['module'].' ('.$module['client'].")\n";

			$map = $this->_mapModule($module);
			extract($map);

			foreach($dirs as $from => $to)
			{
				symlink_dir(realpath2($from), realpath2($to));
			}

			foreach($files as $from => $to)
			{
				symlink_file(realpath2($from), realpath2($to));
			}
		}
	}

	/**
	 * Relinks the plugins
	 */
	public function linkPlugins()
	{
		if(empty($this->_plugins)) return;
		foreach($this->_plugins as $plugin)
		{
			echo "Linking plugin ".$plugin['plugin'].' ('.$plugin['folder'].")\n";

			$map = $this->_mapPlugin($plugin);
			extract($map);

			foreach($dirs as $from => $to)
			{
				symlink_dir(realpath2($from), realpath2($to));
			}

			foreach($files as $from => $to)
			{
				symlink_file(realpath2($from), realpath2($to));
			}
		}
	}

	/**
	 * Remove a list of directories
	 * @param array $dirs
	 * @return boolean
	 */
	private function _unlinkDirectories($dirs)
	{
		foreach($dirs as $dir) {
			if(isLink($dir)) {
				$result = unlink(realpath2($dir));
			} elseif(is_dir($dir)) {
				$result = $this->_rmrecursive($dir);
			} else {
				$result = true;
			}
			if($result === false) return $result;
		}
		return $result;
	}

	/**
	 * Remove a list of files
	 * @param array $files
	 * @return boolean
	 */
	private function _unlinkFiles($files)
	{
		foreach($files as $file) {
			if(isLink($file) || is_file($file)) {
				$result = unlink(realpath2($file));
			} else {
				$result = true;
			}
			if($result === false) return $result;
		}
		return $result;
	}

	/**
	 * Recursively delete a directory
	 * @param string $dir
	 * @return bool
	 */
	private function _rmrecursive($dir)
	{
		// When the directory is a symlink, don't delete recursively. That would
		// fuck up the plugins.
		if(isLink($dir)) {
			return @unlink(realpath2($dir));
		}

		$handle = opendir($dir);
		while( false != ($item = readdir($handle))) {
			if( !in_array($item, array('.','..')) ) {
				$path = $dir.'/'.$item;
				if(isLink($path)) {
					$result = @unlink(realpath2($path));
				} elseif(is_file($path)) {
					$result = @unlink(realpath2($path));
				} elseif(is_dir($path)) {
					$result = $this->_rmrecursive(realpath2($path));
				} else {
					$result = @unlink(realpath2($path));
				}
				if(!$result) return false;
			}
		}
		closedir($handle);

		if(!rmdir($dir)) return false;

		return true;
	}
}

echo <<<ENDBANNER
Akeeba Relinker 2.0
No-configuration extension symlinker
-------------------------------------------------------------------------------
Copyright ©2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
Distributed under the GNU General Public License v3 or later
-------------------------------------------------------------------------------

ENDBANNER;

$config = array();

if($argc >= 2) $config['site'] = $argv[1];
if($argc >= 3) $config['root'] = $argv[2];

$relink = new AkeebaRelink($config);

$relink->unlinkComponent();
$relink->linkComponent();

$relink->unlinkModules();
$relink->linkModules();

$relink->unlinkPlugins();
$relink->linkPlugins();