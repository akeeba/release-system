<?php
/**
 * @package AkeebaReleaseSystem
 * @subpackage plugins.arsdlid
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED') || !class_exists('FOFForm', true))
{
	return;
}

// Required for compatibility with certain operating systems
if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match(
			'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
			array('*' => '.*', '?' => '.?')) . '$/i', $string
		);
	}
}


// Do not run if Akeeba Subscriptions is not enabled
jimport('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_ars', true)) return;

class plgContentArslatest extends JPlugin
{
	/** @var bool Is this category prepared? */
	private $prepared = false;
	
	/** @var array Category titles to category IDs */
	private $categoryTitles = array();
	
	/** @var array The latest release per category, including files */
	private $categoryLatest = array();
	
	/**
	 * Content preparation plugin hook
	 * 
	 * @param srting $context
	 * @param object $row
	 * @param array $params
	 * @param int $limitstart
	 */
	public function onContentPrepare($context, &$row, &$params, $limitstart = 0)
	{
		$text = is_object($row) ? $row->text : $row;
		
		if ( JString::strpos( $row->text, 'arslatest' ) !== false ) {
			if(!$this->prepared) {
				// Deferred initialisation to the very last possible minute
				$this->initialise();
			}
			$regex = "#{arslatest(.*?)}#s";
			$text = preg_replace_callback( $regex, array($this, 'process'), $text );
		}
		
		if(is_object($row)) {
			$row->text = $text;
		} else {
			$row = $text;
		}
	}
	
	/**
	 * preg_match callback to process each match
	 */
	private function process($match)
	{
		$ret = '';
		
		list($op, $content, $pattern) = $this->analyzeString($match[1]);
		switch(strtolower($op)) {
			case 'release':
				$ret = $this->parseRelease($content);
				break;
			case 'release_link':
				$ret = $this->parseReleaseLink($content);
				break;
			case 'item_link':
				$ret = $this->parseItemLink($content, $pattern);
				break;
		}
		
		return $ret;
	}
	
	/**
	 * Inisialises the arrays.
	 */
	private function initialise()
	{
		$model = FOFModel::getTmpInstance('Browses', 'ArsModel')
			->grouping('none')
			->orderby('order');
		$model->processLatest();
		$cats = $model->itemList;
		
		if(!empty($cats)) foreach($cats['all'] as $cat) {
			$cat->title = trim(strtoupper($cat->title));
			$this->categoryTitles[$cat->title] = $cat->id;
			$this->categoryLatest[$cat->id] = $cat->release;
		}
		
		$this->prepared = true;
	}
	
	private function analyzeString($string)
	{
		$op = '';
		$content = '';
		$pattern = '';
		
		$string = trim($string);
		$string = strtoupper($string);
		$parts = explode(' ', $string, 2);
		
		if(count($parts) == 2) {
			$op = trim($parts[0]);
			if(in_array($op, array('RELEASE','RELEASE_LINK'))) {
				$content = trim($parts[1]);
			} elseif($op == 'ITEM_LINK') {
				$content = trim($parts[1]);
				$firstdq = strpos($content, '"');
				if($firstdq !== false) {
					$seconddq = strpos($content, '"', $firstdq + 1);
				} else {
					$seconddq == false;
				}
				if($seconddq !== false) {
					$pattern = trim(substr($content, 0, $seconddq),'"');
					$content = trim(substr($content, $seconddq + 1));
				}
			} else {
				$op = '';
			}
		}
		
		if(empty($op)) $content = '';
		if(empty($content)) $op = '';
		if(empty($content)) $pattern = '';
		
		return array($op, $content, $pattern);
	}
	
	private function getLatestRelease($content)
	{
		$release = null;
		
		if(!array_key_exists($content, $this->categoryTitles)) {
			return $release;
		}
		
		$catid = $this->categoryTitles[$content];
		
		if(!array_key_exists($catid, $this->categoryLatest)) {
			return $release;
		}
		
		$release = $this->categoryLatest[$catid];
		
		if(empty($release)) {
			$release = null;
		}
		
		return $release;
	}
	
	private function parseRelease($content)
	{
		$release = $this->getLatestRelease($content);
		if(empty($release)) return '';
		
		return $release->version;
	}
	
	private function parseReleaseLink($content)
	{
		$release = $this->getLatestRelease($content);
		if(empty($release)) return '';
		
		$releaseid = $release->id;
		$link = JRoute::_('index.php?option=com_ars&view=release&id='.$releaseid);
		
		return $link;
	}
	
	private function parseItemLink($content, $pattern)
	{
		$release = $this->getLatestRelease($content);
		if(empty($release)) return '';
		
		$item = null;
		foreach($release->files as $file)
		{
			if($file->type == 'file') {
				$fname = $file->filename;
			} else {
				$fname = $file->url;
			}
			$fname = strtoupper(basename($fname));
			if(fnmatch($pattern, $fname)) {
				$item = $file;
				break;
			}
		}

		if(empty($item)) return '';
		
		$link = JRoute::_('index.php?option=com_ars&view=download&id='.$item->id);
		
		return $link;
	}
}
