<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ars.php 123 2011-04-13 07:47:16Z nikosdion $
 */

defined('_JEXEC') or die();

class Com_ArsInstallerScript extends ComAkeebaStandardInstallationLibrary
{
	public $parent = null;
	
	public function __construct() {
		$this->_akeeba_extension		= 'com_ars';
		$this->_akeeba_install_sql_path	= 'install.sql';
		$this->_akeeba_script_install	= 'install.ars.php';
		$this->_akeeba_script_update	= 'install.ars.php';
		$this->_akeeba_script_uninstall	= 'uninstall.ars.php';
				
		// Let's try the "session trick"
		$db = JFactory::getDBO();
		$sql = 'DESCRIBE `#__session`';
		$db->setQuery($sql);
		try {
			$ctableAssoc = $db->loadAssocList('Field');
		} catch (Exception $e) {
			$ctableAssoc = '';
		}
		$ctable = empty($ctableAssoc) ? array() : $ctableAssoc;
		if(!empty($ctable)) {
			$type = $ctable['data']['Type'];
			$type = strtolower($type);
			if(strstr($type, 'varchar')) {
				$sql = 'ALTER TABLE `#__session` MODIFY COLUMN `data` MEDIUMTEXT';
				$db->setQuery($sql);
				try {
					$db->query();
				} catch(Exception $e) {
				}
			}
		}
	}
}

/**
 * AkeebaBackup.com Standard Installation Library
 */
class ComAkeebaStandardInstallationLibrary {
	
	protected $_akeeba_extension = '';
	protected $_akeeba_install_sql_path = '';
	protected $_akeeba_script_install = '';
	protected $_akeeba_script_uninstall = '';
	protected $_akeeba_script_update = '';
	
	/**
	 * Joomla! pre-flight event
	 * 
	 * @param string $type Installation type (install, update, discover_install)
	 * @param JInstaller $parent Parent object
	 */
	public function preflight($type, $parent)
	{
		// Joomla! 1.6/1.7 bugfix for "Can not build admin menus"
		if(in_array($type, array('install','discover_install'))) {
			$this->_bugfixDBFunctionReturnedNoError();
		} else {
			$this->_bugfixCantBuildAdminMenus();
		}
	}
	
	public function install($parent) {
		// Copy the install/uninstall scripts
		$this->_copyLegacyScripts($parent);
		// Load the installation script
		$this->parent = $parent->getParent();
		$this->_scriptLoader($this->_akeeba_script_install);
	}

	public function update($parent) {
		// Copy the install/uninstall scripts
		$this->_copyLegacyScripts($parent);
		// Joomla! 1.6/1.7 workaround for not running SQL on updates
		$this->_workaroundApplySQL($parent);
		// Load the udpate script
		$this->parent = $parent->getParent();
		$this->_scriptLoader($this->_akeeba_script_update);
	}
	
	public function uninstall($parent) {
		// Load the uninstallation script
		$this->parent = $parent->getParent();
		$this->_scriptLoader($this->_akeeba_script_uninstall);
	}
	
	private function _scriptLoader($scriptfile)
	{
		if(!defined('_AKEEBA_HACK')) {
			define('_AKEEBA_HACK', 1);
		}
		
		if(file_exists($scriptfile)) {
			require_once($scriptfile);
		} elseif(file_exists(dirname(__FILE__).'/'.$scriptfile)) {
			require_once dirname(__FILE__).'/'.$scriptfile;
		}
		else {
			JError::raiseWarning('42', "Installer script file $scriptfile not found.");
		}
	}
	
	/**
	 * Joomla! 1.6+ bugfix for "DB function returned no error"
	 */
	private function _bugfixDBFunctionReturnedNoError()
	{
		$db = JFactory::getDbo();
			
		// Fix broken #__assets records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->nameQuote('name').' = '.$db->Quote($this->_akeeba_extension));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids = $db->loadColumn();
		} else {
			$ids = $db->loadResultArray();
		}
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__assets')
				->where($db->nameQuote('id').' = '.$db->Quote($id));
			$db->setQuery($query);
			$db->query();
		}

		// Fix broken #__extensions records
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->nameQuote('element').' = '.$db->Quote($this->_akeeba_extension));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids = $db->loadColumn();
		} else {
			$ids = $db->loadResultArray();
		}
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__extensions')
				->where($db->nameQuote('extension_id').' = '.$db->Quote($id));
			$db->setQuery($query);
			$db->query();
		}

		// Fix broken #__menu records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->nameQuote('type').' = '.$db->Quote('component'))
			->where($db->nameQuote('menutype').' = '.$db->Quote('main'))
			->where($db->nameQuote('link').' LIKE '.$db->Quote('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids = $db->loadColumn();
		} else {
			$ids = $db->loadResultArray();
		}
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->nameQuote('id').' = '.$db->Quote($id));
			$db->setQuery($query);
			$db->query();
		}
	}
	
	/**
	 * Joomla! 1.6+ bugfix for "Can not build admin menus"
	 */
	private function _bugfixCantBuildAdminMenus()
	{
		$db = JFactory::getDbo();
		
		// If there are multiple #__extensions record, keep one of them
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->nameQuote('element').' = '.$db->Quote($this->_akeeba_extension));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids = $db->loadColumn();
		} else {
			$ids = $db->loadResultArray();
		}
		if(count($ids) > 1) {
			asort($ids);
			$extension_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__extensions')
					->where($db->nameQuote('extension_id').' = '.$db->Quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}
		
		// @todo
		
		// If there are multiple assets records, delete all except the oldest one
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->nameQuote('name').' = '.$db->Quote($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadObjectList();
		if(count($ids) > 1) {
			asort($ids);
			$asset_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__assets')
					->where($db->nameQuote('id').' = '.$db->Quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// Remove #__menu records for good measure!
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->nameQuote('type').' = '.$db->Quote('component'))
			->where($db->nameQuote('menutype').' = '.$db->Quote('main'))
			->where($db->nameQuote('link').' LIKE '.$db->Quote('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids1 = $db->loadColumn();
		} else {
			$ids1 = $db->loadResultArray();
		}
		if(empty($ids1)) $ids1 = array();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->nameQuote('type').' = '.$db->Quote('component'))
			->where($db->nameQuote('menutype').' = '.$db->Quote('main'))
			->where($db->nameQuote('link').' LIKE '.$db->Quote('index.php?option='.$this->_akeeba_extension.'&%'));
		$db->setQuery($query);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$ids2 = $db->loadColumn();
		} else {
			$ids2 = $db->loadResultArray();
		}
		if(empty($ids2)) $ids2 = array();
		$ids = array_merge($ids1, $ids2);
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->nameQuote('id').' = '.$db->Quote($id));
			$db->setQuery($query);
			$db->query();
		}
	}
	
	/**
	 * Joomla! 1.6+ won't run the SQL file on updates. It will also run none of
	 * the update SQL files the first time you update an extension to a version
	 * which has update SQL files. Therefore, we need a workaround.
	 * 
	 * @return type 
	 */
	private function _workaroundApplySQL($parent)
	{
		$db = JFactory::getDBO();
		if(method_exists($parent, 'extension_root')) {
			$sqlfile = $parent->getPath('extension_root').'/'.$this->_akeeba_install_sql_path;
		} else {
			$sqlfile = $parent->getParent()->getPath('extension_root').'/'.$this->_akeeba_install_sql_path;
		}
		$buffer = file_get_contents($sqlfile);
		if ($buffer !== false) {
			jimport('joomla.installer.helper');
			$queries = JInstallerHelper::splitSql($buffer);
			if (count($queries) != 0) {
				foreach ($queries as $query)
				{
					$query = trim($query);
					if ($query != '' && $query{0} != '#') {
						$db->setQuery($query);
						if (!$db->query()) {
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
							return false;
						}
					}
				}
			}
		}
	}

	/**
	 * Copy the legacy install/uninstall scripts to the component's back-end
	 * @param type $parent 
	 */
	private function _copyLegacyScripts($parent)
	{
		$installFile = (string)$parent->getParent()->getManifest()->installfile;
		if ($installFile) {
			$path['src']	= $parent->getParent()->getPath('source') . '/' . $installFile;
			$path['dest']	= $parent->getParent()->getPath('extension_administrator') . '/' . $installFile;
			$parent->getParent()->copyFiles(array ($path));
		}
		
		$uninstallFile = (string)$parent->getParent()->getManifest()->uninstallfile;
		if ($uninstallFile) {
			$path['src']	= $parent->getParent()->getPath('source') . '/' . $uninstallFile;
			$path['dest']	= $parent->getParent()->getPath('extension_administrator') . '/' . $uninstallFile;
			$parent->getParent()->copyFiles(array ($path));
		}
	}
}