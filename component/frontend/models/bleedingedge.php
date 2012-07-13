<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class ArsModelBleedingedge extends FOFModel
{
	private $category_id;
	private $category;
	private $folder = null;
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/amazons3.php';
	}

	public function setCategory($cat)
	{
		if($cat instanceof ArsTableCategory)
		{
			$this->category = $cat;
			$this->category_id = $cat->id;
		}
		elseif( is_numeric($cat) )
		{
			$this->category_id = (int)$cat;
			$this->category = FOFModel::getTmpInstance('Categories','ArsModel')
				->getItem($this->category_id);
		}

		// Store folder
		$folder = $this->category->directory;
		
		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		if($potentialPrefix == 's3://') {
			$check = substr($folder, 5);
			$s3 = ArsHelperAmazons3::getInstance();
			$items = $s3->getBucket('', $check.'/');
			if(empty($items)) {
				return;
			}
		} else {
			jimport('joomla.filesystem.folder');
			if(!JFolder::exists($folder)) {
				$folder = JPATH_ROOT.'/'.$folder;
				if(!JFolder::exists($folder)) return;
			}
		}
		$this->folder = $folder;
	}

	public function scanCategory($a_category = null)
	{
		if(!empty($a_category)) {
			$this->setCategory($a_category);
		}

		// Can't proceed without a category
		if(empty($this->category)) return;

		// Can't proceed if it's not a bleedingedge category
		if($this->category->type != 'bleedingedge') return;

		// Check for releases
		$this->checkReleases();
	}

	private function checkReleases($a_category = null)
	{
		if(!empty($a_category)) {
			$this->setCategory($a_category);
		}

		$allReleases = FOFModel::getTmpInstance('Releases','ArsModel')
			->category($this->category->id)
			->order('created')
			->dir('desc')
			->limitstart(0)
			->limit(0)
			->getItemList(true);

		$potentialPrefix = substr($this->category->directory, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = ($potentialPrefix == 's3://');
		
		jimport('joomla.filesystem.folder');

		$known_folders = array();

		// Make sure published releases do exist
		if(!empty($allReleases))
		{
			foreach($allReleases as $release)
			{
				$folder = $this->folder.'/'.$release->alias;
				$known_folders[] = $release->alias;

				if(!$release->published) continue;

				$exists = false;
				if($useS3) {
					$check = substr($folder, 5);
					$s3 = ArsHelperAmazons3::getInstance();
					$items = $s3->getBucket('', $check.'/');
					$exists = !empty($items);
				} else {
					$exists = JFolder::exists($folder);
				}
				
				if(!$exists) {
					$release->published = 0;
					
					$tmp = FOFModel::getTmpInstance('Releases','ArsModel')
						->getTable()
						->save($release);
				} else {
					$this->checkFiles($release);
				}
			}
			$first_release = array_shift($allReleases);
		}
		else
		{
			$first_release = null;
		}

		jimport('joomla.filesystem.file');
		$first_changelog = array();
		if(!empty($first_release))
		{
			$changelog = $this->folder.'/'.$first_release->alias.'/CHANGELOG';
			
			$hasChangelog = false;
			if($useS3) {
				$s3 = ArsHelperAmazons3::getInstance();
				$response = $s3->getObject('', substr($changelog,5));
				$hasChangelog = $response !== false;
				if($hasChangelog) {
					$first_changelog = $response->body;
				}
			} else {
				if(JFile::exists($changelog)) {
					$hasChangelog = true;
					$first_changelog = JFile::read($changelog);
				}
			}
			
			if($hasChangelog) {
				if(!empty($first_changelog)) {
					$first_changelog = explode("\n", str_replace("\r\n", "\n", $first_changelog));
				} else {
					$first_changelog = array();
				}
			}
		}

		// Get a list of all folders
		if($useS3) {
			$allFolders = array();
			$everything = $this->_listS3Contents($this->folder);
			$dirLength = strlen($this->folder) - 5;
			if(count($everything)) foreach($everything as $path => $info) {
				if(!array_key_exists('size', $info) && (substr($path, -1) == '/')) {
					if(substr($path, 0, $dirLength) == substr($this->folder,5)) {
						$path = substr($path, $dirLength);
					}
					$path = trim($path,'/');
					$allFolders[] = $path;
				}
			}
		} else {
			$allFolders = JFolder::folders($this->folder);
		}
		if(!empty($allFolders)) foreach($allFolders as $folder)
		{
			if(!in_array($folder, $known_folders))
			{
				// Create a new entry
				$notes = '';

				$changelog = $this->folder.'/'.$folder.'/'.'CHANGELOG';
				
				$hasChangelog = false;
				if($useS3) {
					$s3 = ArsHelperAmazons3::getInstance();
					$response = $s3->getObject('', substr($changelog,5));
					$hasChangelog = $response !== false;
					if($hasChangelog) {
						$this_changelog = $response->body;
					}
				} else {
					if(JFile::exists($changelog)) {
						$hasChangelog = true;
						$this_changelog = JFile::read($changelog);
					}
				}
				
				if($hasChangelog)
				{
					if(!empty($this_changelog)) {
						$this_changelog = explode("\n", str_replace("\r\n", "\n", $this_changelog));
						$notes = '<h3>Changelog</h3><ul>';
						foreach($this_changelog as $line)
						{
							if(in_array($line, $first_changelog)) continue;
							$notes .= '<li>'.$this->colorise($line)."</li>\n";
						}
						$notes .= '</ul>';
					}
				} else {
					$this_changelog = '';
				}

				jimport('joomla.utilities.date');
				$jNow = new JDate();
				$data = array(
					'id'				=> 0,
					'category_id'		=> $this->category_id,
					'version'			=> $folder,
					'alias'				=> $folder,
					'maturity'			=> 'alpha',
					'description'		=> '',
					'notes'				=> $notes,
					'groups'			=> $this->category->groups,
					'access'			=> $this->category->access,
					'published'			=> 1,
					'created'			=> $jNow->toSql(),
				);
				
				// Before saving the release, call the onNewARSBleedingEdgeRelease()
				// event of ars plugins so that they have the chance to modify
				// this information.
				// -- Load plugins
				jimport('joomla.plugin.helper');
				JPluginHelper::importPlugin('ars');
				// -- Setup information data
				$infoData = array(
					'folder'			=> $folder,
					'category_id'		=> $this->category_id,
					'category'			=> $this->category,
					'has_changelog'		=> $hasChangelog,
					'changelog_file'	=> $changelog,
					'changelog'			=> $this_changelog,
					'first_changelog'	=> $first_changelog
				);
				// -- Trigger the plugin event
				$app = JFactory::getApplication();
				$jResponse = $app->triggerEvent('onNewARSBleedingEdgeRelease',array(
					$infoData,
					$data
				));
				// -- Merge response
				if(is_array($jResponse)) foreach($jResponse as $response) {
					if(is_array($response)) {
						$data = array_merge($data, $response);
					}
				}
				// -- Create the BE release
				$table = FOFModel::getTmpInstance('Releases','ArsModel')
						->getTable();
				$table->save($data,'category_id');
				$this->checkFiles($table);
			}
		}
	}

	public function checkFiles($release)
	{
		if(empty($this->folder))
		{
			$this->setCategory($release->category_id);
		}
		if($this->category->type != 'bleedingedge') return;

		$folder = $this->folder.'/'.$release->alias;
		
		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = ($potentialPrefix == 's3://');

		// Do we have a changelog?
		if(empty($release->notes))
		{
			$changelog = $folder.'/CHANGELOG';
			$hasChangelog = false;
			if($useS3) {
				$s3 = ArsHelperAmazons3::getInstance();
				$response = $s3->getObject('', substr($changelog,5));
				$hasChangelog = $response !== false;
				if($hasChangelog) {
					$this_changelog = $response->body;
				}
			} else {
				if(JFile::exists($changelog)) {
					$hasChangelog = true;
					$this_changelog = JFile::read($changelog);
				}
			}
			
			if($hasChangelog)
			{
				$notes = '';
				$this_changelog = explode("\n", str_replace("\r\n", "\n", $this_changelog));
				$notes = '<h3>Changelog</h3><p>';
				foreach($this_changelog as $line)
				{
					$notes .= $this->colorise($line)."<br/>\n";
				}
				$notes .= '</p>';
				$release->notes = $notes;

				$table = FOFModel::getTmpInstance('Releases','ArsModel')
						->getTable()
						->save($release,'category_id');
			}
		}

		$allItems = FOFModel::getTmpInstance('Items','ArsModel')
			->release($release->id)
			->limitstart(0)
			->getItemList(true);

		$known_items = array();
		if($useS3) {
			$files = array();
			$everything = $this->_listS3Contents($folder);
			$dirLength = strlen($folder) - 5;
			if(count($everything)) foreach($everything as $path => $info) {
				if(array_key_exists('size', $info) && (substr($path, -1) != '/')) {
					if(substr($path, 0, $dirLength) == substr($folder,5)) {
						$path = substr($path, $dirLength);
					}
					$path = trim($path,'/');
					$files[] = $path;
				}
			}
		} else {
			$files = JFolder::files($folder);
		}

		if(!empty($allItems)) foreach($allItems as $item)
		{
			$known_items[] = basename($item->filename);
			//if(!JFile::exists($this->folder.'/'.$item->filename) && !JFile::exists(JPATH_ROOT.'/'.$this->folder.'/'.$item->filename))
			if($item->published && !in_array(basename($item->filename), $files)) {
				$table = FOFModel::getTmpInstance('Items','ArsModel')->getTable();
				$item->published = 0;
				$table->save($item);
			} if(!$item->published && in_array(basename($item->filename), $files)) {
				$table = FOFModel::getTmpInstance('Items','ArsModel')->getTable();
				$item->published = 1;
				$table->save($item);
			}
		}

		if(!empty($files)) foreach($files as $file)
		{
			if( basename($file) == 'CHANGELOG' ) continue;

			if(in_array($file, $known_items)) continue;
			
			jimport('joomla.utilities.date');
			$jNow = new JDate();
			$data = array(
				'id'				=> 0,
				'release_id'		=> $release->id,
				'description'		=> '',
				'type'				=> 'file',
				'filename'			=> $release->alias.'/'.$file,
				'url'				=> '',
				'groups'			=> $release->groups,
				'hits'				=> '0',
				'published'			=> '1',
				'created'			=> $jNow->toSql(),
				'access'			=> '1'
			);
			
			// Before saving the item, call the onNewARSBleedingEdgeItem()
			// event of ars plugins so that they have the chance to modify
			// this information.
			// -- Load plugins
			jimport('joomla.plugin.helper');
			JPluginHelper::importPlugin('ars');
			// -- Setup information data
			$infoData = array(
				'folder'			=> $folder,
				'file'				=> $file,
				'release_id'		=> $release->id,
				'release'			=> $release
			);
			// -- Trigger the plugin event
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onNewARSBleedingEdgeItem',array(
				$infoData,
				$data
			));
			// -- Merge response
			if(is_array($jResponse)) foreach($jResponse as $response) {
				if(is_array($response)) {
					$data = array_merge($data, $response);
				}
			}
			
			if(isset($data['ignore'])) {
				if($data['ignore']) continue;
			}
			
			$table = clone FOFModel::getTmpInstance('Items','ArsModel')->getTable();
			$table->reset();
			$result = $table->save($data);
		}

		if(isset($table)) $table->reorder('`release_id` = '.$release->id);
	}

	private function colorise($line)
	{
		$line = trim($line);
		$line_type = substr($line,0,1);
		$style = '';
		switch($line_type)
		{
			case '+':
				$style = 'added';
				$line = trim(substr($line,1));
				break;
			case '-':
				$style = 'removed';
				$line = trim(substr($line,1));
				break;
			case '#':
				$style = 'bugfix';
				$line = trim(substr($line,1));
				break;
			case '~':
				$style = 'minor';
				$line = trim(substr($line,1));
				break;
			case '!':
				$style = 'important';
				$line = trim(substr($line,1));
				break;
			default:
				$style = 'default';
				break;
		}

		return "<span class=\"ars-devrelease-changelog-$style\">$line</span>";
	}
	
	private function _listS3Contents($path = null)
	{
		static $lastDirectory = null;
		static $lastListing = array();
		
		$directory = substr($path, 5);
		
		if($lastDirectory != $directory) {
			if($directory == '/') {
				$directory = null;
			} else {
				$directory = trim($directory,'/').'/';
			}
			$s3 = ArsHelperAmazons3::getInstance();
			$lastListing = $s3->getBucket('', $directory, null, null, '/', true);
		}
		return $lastListing;
	}
}