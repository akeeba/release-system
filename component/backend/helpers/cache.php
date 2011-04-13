<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

class ArsHelperCache
{
	private $cachepath = null;
	private $domain = null;
	private $lastUpdate = null;
	private $registry = null;
	private $hasCache = null;
	
	public function __construct($domain = 'cpanelstats', $ttl = 900)
	{
		// Get the domain
		$filter = JFilterInput::getInstance();
		$domain = $filter->clean($domain, 'CMD');
		$this->domain = $domain;
		
		// Get the cache paths
		$this->cachepath = JPATH_CACHE.DS.'com_ars'.DS.$domain.'.ini';
		
		// Create a new registry
		$this->registry = JRegistry::getInstance('arscache');
		
		// Load the registry
		$this->hasCache = true;
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		if(JFolder::exists(dirname($this->cachepath))) {
			if(JFile::exists($this->cachepath)) {
				$this->lastUpdate = @filemtime($this->cachepath);
				if($this->lastUpdate === false) $this->lastUpdate = 0;
				if($this->lastUpdate != 0) {
					$now = time();
					if($this->lastUpdate > ($now - $ttl)) {
						// Only loads cache if its age is at least $ttl seconds since now
						$this->registry->loadFile($this->cachepath,'INI');
					}
				}
			} else {
				$this->lastUpdate = 0;
			}
		} else {
			$this->lastUpdate = 0;
			$result = JFolder::create(dirname($this->cachepath));
			if(!$result) $this->hasCache = false;
		}
	}
	
	public function getValue($key, $default = null)
	{
		if(!$this->hasCache) {
			return $default;
		} else {
			return $this->registry->getValue($key, $default);
		}
	}
	
	public function setValue($key, $value)
	{
		if(!$this->hasCache) {
			return;
		} else {
			$this->registry->setValue($key, $value);
		}
	}
	
	public function save()
	{
		if(!$this->hasCache) {
			return;
		} else {
			$serialized = $this->registry->toString('INI');
			$result = JFile::write($this->cachepath, $serialized);
		}
	}
}