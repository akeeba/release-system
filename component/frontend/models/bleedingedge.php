<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelBleedingedge extends JModel
{
	private $category_id;
	private $category;
	private $folder = null;

	public function setCategory($cat)
	{
		if($cat instanceof TableCategories)
		{
			$this->category = $cat;
			$this->category_id = $cat->id;
		}
		elseif( is_numeric($cat) )
		{
			$this->category_id = (int)$cat;
			$model = JModel::getInstance('Categories','ArsModel');
			$model->setId( $this->category_id );
			$this->category = $model->getItem();
		}

		// Store folder
		$folder = $this->category->directory;
		jimport('joomla.filesystem.folder');
		if(!JFolder::exists($folder)) {
			$folder = JPATH_ROOT.DS.$folder;
			if(!JFolder::exists($folder)) return;
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

		$model = JModel::getInstance('Releases','ArsModel');
		$model->reset();
		$model->setState('category', $this->category->id);
		$model->setState('order','created');
		$model->setState('dir','desc');
		$model->setState('limitstart',0);
		$model->setState('limit',0);
		$allReleases = $model->getItemList();

		jimport('joomla.filesystem.folder');

		$known_folders = array();

		// Make sure published releases do exist
		if(!empty($allReleases))
		{
			foreach($allReleases as $release)
			{
				$folder = $this->folder.DS.$release->alias;
				$known_folders[] = $release->alias;

				if(!$release->published) continue;

				if(!JFolder::exists($folder)) {
					$release->published = 0;
					$table = JTable::getInstance('Releases','Table');
					$table->save($release);
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
			$changelog = $this->folder.DS.$first_release->alias.DS.'CHANGELOG';
			if(JFile::exists($changelog)) {
				$first_changelog = JFile::read($changelog);
				if(!empty($first_changelog)) {
					$first_changelog = explode("\n", str_replace("\r\n", "\n", $first_changelog));
				} else {
					$first_changelog = array();
				}
			}
		}

		// Get a list of all folders
		$allFolders = JFolder::folders($this->folder);
		if(!empty($allFolders)) foreach($allFolders as $folder)
		{
			if(!in_array($folder, $known_folders))
			{
				// Create a new entry
				$notes = '';
				
				if(JFile::exists($changelog))
				{
					$this_changelog = JFile::read($this->folder.DS.$folder.DS.'CHANGELOG');
					if(!empty($this_changelog)) {
						$this_changelog = explode("\n", str_replace("\r\n", "\n", $this_changelog));
						$notes = '<h1>Changelog</h1><p>';
						foreach($this_changelog as $line)
						{
							if(in_array($line, $first_changelog)) continue;
							$notes .= $this->colorise($line)."<br/>\n";
						}
						$notes .= '</p>';
					}
				}

				$table = JTable::getInstance('Releases','Table');
				$table->reset();
				$data = array(
					'id'				=> 0,
					'category_id'		=> $this->category_id,
					'version'			=> $folder,
					'alias'				=> $folder,
					'maturity'			=> 'alpha',
					'description'		=> '',
					'notes'				=> $notes,
					'groups'			=> $this->category->groups,
					'access'			=> $this->caregory->access,
					'published'			=> 1
				);
				$table->save($data);
				$table->reorder('`category_id` = '.$this->category_id);
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

		$folder = $this->folder;

		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setState('release', $release->id);
		$model->setState('limitstart',0);
		$model->setState('limit',0);
		$allItems = $model->getItemList();

		$known_items = array();
		if(!empty($allItems)) foreach($allItems as $item)
		{
			$known_items[] = $item->filename;
			if(!$item->published) continue;
			if(!JFile::exists($folder.DS.$item->filename) && !JFile::exists(JPATH_ROOT.DS.$item->filename))
			{
				$table = JTable::getInstance('Items','Table');
				$item->published = 0;
				$table->save($item);
			}
		}

		$files = JFolder::files($folder);
		if(!empty($files)) foreach($files as $file)
		{
			if(in_array($file, $known_items)) continue;
			$data = array(
				'release_id'		=> $release->id,
				'title'				=> $file,
				'alias'				=> $file,
				'description'		=> '',
				'type'				=> 'file',
				'filename'			=> $release->alias.'/'.$file,
				'url'				=> '',
				'groups'			=> $release->groups,
				'hits'				=> 0,
				'published'			=> 1
			);
			$table = JTable::getInstance('Items','Table');
			$table->save($data);
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
				$style = 'color: #006600; font-weight: bold;';
				break;
			case '-':
				$style = 'color: #660000';
				break;
			case '#':
				$style = 'color: #000000';
				break;
			case '~':
				$style = 'color: #ccc; font-style: italics;';
				break;
			case '!':
				$style = 'color: red; background: yellow; font-weight: bold;';
				break;
		}

		$line = trim(substr($line,1));
		return "<span style=\"$style\">$line</span>";
	}
}