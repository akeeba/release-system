<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelUpload extends JModel
{
	function &getCategories()
	{
		$model = JModel::getInstance('Categories','ArsModel');
		$model->reset();
		$categories = $model->getItemList(true);
		return $categories;
	}

	/**
	 * Gets the folder of the current category, whose ID is set in the
	 * 'category' state variable
	 * 
	 * @staticvar string $folder
	 * @return string The folder path, or an empty string if it's not found
	 */
	function getCategoryFolder()
	{
		static $folder = null;
		if(empty($folder))
		{
			$category_id = $this->getState('category',0);
			$model = JModel::getInstance('Categories','ArsModel');
			$model->setId((int)$category_id);
			$category = $model->getItem();

			if(empty($category))
			{
				$folder = '';
			}
			else
			{
				$folder = $category->directory;

				jimport('joomla.filesystem.folder');
				if(!JFolder::exists($folder))
				{
					$folder = JPATH_ROOT.DS.$folder;
					if(!JFolder::exists($folder))
					{
						$folder = '';
					}
				}
			}

			$subfolder = $this->getState('folder','');
			if(!empty($subfolder))
			{
				// Clean and check subfolder
				$subfolder = JPath::clean($subfolder);
				if (strpos($subfolder, '..') !== false) {
					JError::raiseError( 20, 'ARS - Use of relative paths not permitted'); // don't translate
					jexit();
				}
				// Find the parent path to our subfolder
				$parent = JPath::clean( @realpath($folder.DS.$subfolder.DS.'..') );
				$parent = trim( str_replace(JPath::clean($folder), '', $parent) , '/\\' );
				$this->setState('parent',$parent);

				// Calculate the full path to the subfolder
				$folder = JPath::clean($folder.DS.$subfolder);
				$this->setState('parent',$parent);
				$this->setState('folder',$subfolder);
			}
			else
			{
				$this->setState('parent',null);
			}
		}

		return $folder;
	}

	function getFiles()
	{
		$files = array();
		$folder = $this->getCategoryFolder();
		if(empty($folder)) return $files;

		jimport('joomla.filesystem.folder');
		$files = JFolder::files($folder);
		return $files;
	}

	function getFolders()
	{
		$folders = array();
		$folder = $this->getCategoryFolder();
		if(empty($folder)) return $folders;

		jimport('joomla.filesystem.folder');
		$folders = JFolder::folders($folder);
		return $folders;
	}

	function delete()
	{
		$folder = $this->getCategoryFolder();
		$file = $this->getState('file','');
		if(empty($file)) return '';

		$filepath = $folder.DS.$file;

		jimport('joomla.filesystem.file');
		if(!JFile::exists($filepath)) return false;

		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setState('category', $this->getState('category',0));
		$model->setState('filename', $this->getState('file',''));
		$files = $model->getItemList();

		if(!empty($files))
		{
			// Unpublish entries
			foreach($files as $entry)
			{
				$model->setId($entry->id);
				$item = $model->getItem();
				$item->published = 0;
				$model->save($item);
			}
		}

		return JFile::delete($filepath);
	}
}