<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

if(!class_exists('ArsTable'))
{
	require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'tables'.DS.'base.php';
}

if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match(
			'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
			array('*' => '.*', '?' => '.?')) . '$/i', $string
		);
	}
}

class TableItems extends ArsTable
{
	var $id = 0;
	var $release_id = 0;
	var $title = '';
	var $alias = '';
	var $description = '';
	var $type = '';
	var $filename = '';
	var $url = '';
	var $groups = '';
	var $hits = 0;
	var $created = null;
	var $created_by = 0;
	var $modified = '0000-00-00 00:00:00';
	var $modified_by = 0;
	var $checked_out = 0;
	var $checked_out_time = '0000-00-00 00:00:00';
	var $ordering = 0;
	var $access = 0;
	var $published = 0;
	var $updatestream = 0;
	var $md5 = '';
	var $sha1 = '';
	var $filesize = 0;

	function __construct( &$db )
	{
		parent::__construct( '#__ars_items', 'id', $db );
		
		$baseAccess = version_compare(JVERSION,'1.6.0','ge') ? 1 : 0;
		$this->access = $baseAccess;
	}

	function check()
	{
		// If the release is missing, throw an error
		if(!$this->release_id) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_CATEGORY'));
			return false;
		}

		// Get some useful info
		$db = $this->getDBO();
		$sql = 'SELECT `title`, `alias` FROM `#__ars_items` WHERE `release_id` = '.(int)$this->release_id;
		if($this->id) {
			$sql .= ' AND NOT(`id`='.(int)$this->id.')';
		}
		$db->setQuery($sql);
		$info = $db->loadAssocList('title');

		$info = $db->loadAssocList();
		$titles = array(); $aliases = array();
		foreach($info as $infoitem)
		{
			$titles[] = $infoitem['title'];
			$aliases[] = $infoitem['alias'];
		}

		// Let's get automatic item title/description records
		$sql = 'SELECT * FROM `#__ars_autoitemdesc` WHERE `category` IN (SELECT `category_id` FROM `#__ars_releases` WHERE `id` = '.$db->Quote($this->release_id).') AND NOT `published` = 0';
		$db->setQuery($sql);
		$autoitems = $db->loadObjectList();
		$auto = (object)array('title'=>'','description'=>'');
		if(!empty($autoitems))
		{
			$fname = basename( (($this->type == 'file') ? $this->filename : $this->url) );
			foreach($autoitems as $autoitem)
			{
				$pattern = $autoitem->packname;
				if(empty($pattern)) continue;

				if(fnmatch($pattern, $fname))
				{
					$auto = $autoitem;
					break;
				}
			}
		}

		// Check if a title exists
		if(!$this->title) {
			// No, try the automatic rule-based title
			$this->title = $auto->title;
			if(!$this->title)
			{
				// No, try to get the filename
				switch($this->type)
				{
					case 'file':
						if($this->filename) $this->title = basename($this->filename);
						break;

					case 'link':
						if($this->url) $this->title = basename($this->url);
						break;
				}

				if(!$this->title)
				{
					// Aw, no title could be set. Sorry, I've got to throw an error.
					$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE'));
					return false;
				}
			}
		}

		if(in_array($this->title, $titles)) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_TITLE_UNIQUE'));
			return false;
		}

		$stripDesc = strip_tags($this->description);
		$stripDesc = trim($stripDesc);
		if(empty($this->description) || empty($stripDesc))
		{
			$this->description = $auto->description;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			$source = $this->title;
			switch($this->type)
			{
				case 'file':
					if($this->filename) $source = basename($this->filename);
					break;

				case 'link':
					if($this->url) $source = basename($this->url);
					break;
			}
			$this->alias = str_replace('.','-',$this->alias);

			// Create a smart alias
			$alias = strtolower($source);
			$alias = str_replace(' ', '-', $alias);
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		if(!$this->alias) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS'));
			return false;
		}

		if(in_array($this->alias, $aliases)) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_ALIAS_UNIQUE'));
			return false;
		}

		// Do we have a type?
		if(!in_array($this->type,array('link','file')))
		{
			$this->setError(JText::_('ERR_ITEM_NEEDS_TYPE'));
			return false;
		}

		// Check for filename or url
		if( ($this->type == 'file') && !($this->filename) ) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_FILENAME'));
			return false;
		} elseif( ($this->type == 'link') && !($this->url) ) {
			$this->setError(JText::_('ERR_ITEM_NEEDS_LINK'));
			return false;
		}

		jimport('joomla.filter.filterinput');
		$filter = JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if(!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);
		// Set the access to registered if there are Ambra groups defined
		$baseAccess = version_compare(JVERSION,'1.6.0','ge') ? 1 : 0;
		if(!empty($this->groups) && ($this->access == $baseAccess))
		{
			$this->access = $baseAccess + 1;
		}

		jimport('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if(!$this->created_by && empty($this->id))
		{
			$this->created_by = $user->id;
			$this->created = $date->toMySQL();
		}
		else
		{
			$this->modified_by = $user->id;
			$this->modified = $date->toMySQL();
		}

		/*
		if(empty($this->ordering)) {
			$this->ordering = $this->getNextOrder();
		}
		*/

		if( is_null($this->published) || ($this->published == '') )
		{
			$this->published = 0;
		}

		// Apply an update stream, if possible
		if(empty($this->updatestream))
		{
			$db = $this->getDBO();
			$sql = 'SELECT * FROM `#__ars_updatestreams` WHERE `category` IN (SELECT `category_id` FROM `#__ars_releases` WHERE `id` = '.$db->Quote($this->release_id).')';
			$db->setQuery($sql);
			$streams = $db->loadObjectList();
			if(!empty($streams))
			{
				$fname = basename( (($this->type == 'file') ? $this->filename : $this->url) );
				foreach($streams as $stream)
				{
					$pattern = $stream->packname;
					$element = $stream->element;
					if(empty($pattern) && !empty($element))
					{
						$pattern = $element.'*';
					}

					if(empty($pattern)) continue;

					if(fnmatch($pattern, $fname))
					{
						$this->updatestream = $stream->id;
						break;
					}
				}
			}
		}

		// Check for MD5 and SHA1 existence
		if( empty($this->md5) || empty($this->sha1) || empty($this->filesize) )
		{
			if($this->type == 'file') {
				$target = null;
				$folder = null;
				$filename = $this->filename;

				$relModel = JModel::getInstance('Releases','ArsModel');
				$relModel->reset();
				$relModel->setId($this->release_id);
				$release = $relModel->getItem();

				if(is_object($release))
				{
					$catModel = JModel::getInstance('Categories','ArsModel');
					$catModel->reset();
					$catModel->setId($release->category_id);
					$category = $catModel->getItem();

					if(is_object($category)) {
						$folder = $category->directory;
					}
				}

				if(!empty($folder))
				{
					jimport('joomla.filesystem.folder');
					if(!JFolder::exists($folder)) {
						$folder = JPATH_ROOT.DS.$folder;
						if(!JFolder::exists($folder)) $folder = null;
					}
				}

				if(!empty($folder)) {
					$filename = $folder.DS.$filename;
				}
			}
			elseif($this->type == 'link')
			{
				$url = $this->url;
				$config =& JFactory::getConfig();
				$target = $config->getValue('config.tmp_path').DS.'temp.dat';

				if(function_exists('curl_exec'))
				{
					// By default, try using cURL
					$process = curl_init($url);
					curl_setopt($process, CURLOPT_HEADER, 0);
					// Pretend we are IE7, so that webservers play nice with us
					curl_setopt($process, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');
					curl_setopt($process, CURLOPT_TIMEOUT, 5);
					curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
					// The @ sign allows the next line to fail if open_basedir is set or if safe mode is enabled
					@curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
					@curl_setopt($process, CURLOPT_MAXREDIRS, 20);
					$data = curl_exec($process);

					if($data !== false)
					{
						jimport('joomla.filesystem.file');
						$result = JFile::write($target, $data);
					}
					curl_close($process);
				}
				else
				{
					// Use Joomla!'s download helper
					jimport('joomla.installer.helper');
					JInstallerHelper::downloadPackage($url, $target);
				}

				$filename = $target;
			}

			if(!empty($filename)) {
				jimport('joomla.filesystem.file');
				if(!JFile::exists($filename)) {
					$filename = null;
				}
			}

			if(!empty($filename)) {
				if(function_exists('hash_file')) {
					$this->md5 = hash_file('md5', $filename);
					$this->sha1 = hash_file('sha1', $filename);
				} else {
					if(function_exists('md5_file')) {
						$this->md5 = md5_file($filename);
					}
					if(function_exists('sha1_file')) {
						$this->sha1 = sha1_file($filename);
					}
				}

				$filesize = @filesize($filename);
				if($filesize !== false) $this->filesize = $filesize;
			}

			if(!empty($target))
			{
				JFile::delete($target);
			}
		}

		return true;
	}
}