<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.application.component.model');

class ArsModelBleedingedge extends F0FModel
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
			$this->category = F0FModel::getTmpInstance('Categories','ArsModel')
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
			JLoader::import('joomla.filesystem.folder');
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

		$allReleases = F0FModel::getTmpInstance('Releases','ArsModel')
			->category($this->category->id)
			->order('created')
			->dir('desc')
			->limitstart(0)
			->limit(0)
			->getItemList(true);

		$potentialPrefix = substr($this->category->directory, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = ($potentialPrefix == 's3://');

		JLoader::import('joomla.filesystem.folder');

		$known_folders = array();

		// Make sure published releases do exist
		if(!empty($allReleases))
		{
			foreach($allReleases as $release)
			{
				$releaseFolder = $this->getReleaseFolder($release);

				if ($releaseFolder !== false)
				{
					$known_folders[] = $releaseFolder;
				}

				if(!$release->published) continue;

				if(!$releaseFolder) {
					$release->published = 0;

					$tmp = F0FModel::getTmpInstance('Releases','ArsModel')
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

		JLoader::import('joomla.filesystem.file');
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
						$notes = $this->coloriseChangelog($this_changelog, $first_changelog);
					}
				} else {
					$this_changelog = '';
				}

				JLoader::import('joomla.utilities.date');
				$jNow = new JDate();

				$alias = str_replace(array(' ', '.'), '-', $folder);

				$data = array(
					'id'				=> 0,
					'category_id'		=> $this->category_id,
					'version'			=> $folder,
					'alias'				=> $alias,
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
				JLoader::import('joomla.plugin.helper');
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
				$table = F0FModel::getTmpInstance('Releases','ArsModel')
						->getTable();
				$table->save($data,'category_id');
				$this->checkFiles($table);
			}
		}
	}

	/**
	 * Gets the release folder.
	 *
	 * This is required for compatibility with FOLDER_MATURITY of plgArsBleedingedgematurity.
	 *
	 * @param 	object	$release	The release.
	 *
	 * @return 	bool|string			The release folder if found, or false otherwise.
	 */
	private function getReleaseFolder($release)
	{
		JLoader::import('joomla.filesystem.folder');

		$releaseFolder = false;

		$folders = array(
			$release->version,
			$release->version . '_' . strtoupper($release->maturity)
		);

		foreach ($folders as $folder)
		{
			$potentialPrefix = substr($folder, 0, 5);
			$potentialPrefix = strtolower($potentialPrefix);
			$useS3 = ($potentialPrefix == 's3://');

			if ($useS3)
			{
				$folder = substr($folder, 5);
				$s3 = ArsHelperAmazons3::getInstance();

				$items = $s3->getBucket('', $folder . '/');
				$exists = !empty($items);

				if ($exists)
				{
					$releaseFolder = $folder;

					break;
				}
			}
			else
			{
				if (JFolder::exists($this->folder . '/' . $folder))
				{
					$releaseFolder = $folder;

					break;
				}
			}
		}

		return $releaseFolder;
	}

	public function checkFiles($release)
	{
		if(empty($this->folder))
		{
			$this->setCategory($release->category_id);
		}
		if($this->category->type != 'bleedingedge') return;

		$releaseFolder = $this->getReleaseFolder($release);
		$folder = $this->folder . '/' . $releaseFolder;

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
				$notes = $this->coloriseChangelog($this_changelog, $first_changelog);
				$release->notes = $notes;

				$table = F0FModel::getTmpInstance('Releases','ArsModel')
						->getTable()
						->save($release,'category_id');
			}
		}

		$allItems = F0FModel::getTmpInstance('Items','ArsModel')
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
				$table = F0FModel::getTmpInstance('Items','ArsModel')->getTable();
				$item->published = 0;
				$table->save($item);
			} if(!$item->published && in_array(basename($item->filename), $files)) {
				$table = F0FModel::getTmpInstance('Items','ArsModel')->getTable();
				$item->published = 1;
				$table->save($item);
			}
		}

		if(!empty($files)) foreach($files as $file)
		{
			if( basename($file) == 'CHANGELOG' ) continue;

			if(in_array($file, $known_items)) continue;

			$releaseFolder = $this->getReleaseFolder($release);

			JLoader::import('joomla.utilities.date');
			$jNow = new JDate();
			$data = array(
				'id'				=> 0,
				'release_id'		=> $release->id,
				'description'		=> '',
				'type'				=> 'file',
				'filename'			=> $releaseFolder . '/' . $file,
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
			JLoader::import('joomla.plugin.helper');
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

			$table = clone F0FModel::getTmpInstance('Items','ArsModel')->getTable();
			$table->reset();
			$result = $table->save($data);
		}

		if(isset($table)) $table->reorder('`release_id` = '.$release->id);
	}

	private function coloriseChangelog(&$this_changelog, $first_changelog = array())
	{
		$this_changelog = explode("\n", str_replace("\r\n", "\n", $this_changelog));
		if(empty($this_changelog)) {
			return '';
		}
		$notes = '';

		JLoader::import('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_ars');

		$generate_changelog = $params->get('begenchangelog', 1);
		$colorise_changelog = $params->get('becolorisechangelog', 1);

		if($generate_changelog) {
			if($colorise_changelog) {
				$notes = '<h3>'.$changelog_header.'</h3>';
			}
			$notes .= '<ul>';

			foreach($this_changelog as $line)
			{
				if(in_array($line, $first_changelog)) continue;
				if($colorise_changelog) {
					$notes .= '<li>'.$this->colorise($line)."</li>\n";
				} else {
					$notes .= "<li>$line</li>\n";
				}
			}
			$notes .= '</ul>';
		}

		return $notes;
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
