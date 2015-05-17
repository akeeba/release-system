<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelUploads extends F0FModel
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once JPATH_ADMINISTRATOR . '/components/com_ars/helpers/amazons3.php';
	}

	public function &getCategories()
	{
		return F0FModel::getTmpInstance('Categories', 'ArsModel')
					   ->getItemList(true);
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

		if (empty($folder))
		{
			$category_id = $this->getState('category', 0);
			$category = F0FModel::getTmpInstance('Categories', 'ArsModel')
								->getItem((int)$category_id);

			if (empty($category))
			{
				$folder = '';
			}
			else
			{
				$folder = $category->directory;

				$potentialPrefix = substr($folder, 0, 5);
				$potentialPrefix = strtolower($potentialPrefix);
				$useS3 = $potentialPrefix == 's3://';

				if (!$useS3)
				{
					JLoader::import('joomla.filesystem.folder');
					if (!JFolder::exists($folder))
					{
						$folder = JPATH_ROOT . '/' . $folder;
						if (!JFolder::exists($folder))
						{
							$folder = '';
						}
					}
				}
			}

			if (empty($folder))
			{
				return $folder;
			}

			$subfolder = $this->getState('folder', '');

			if (!empty($subfolder))
			{
				if ($useS3)
				{
					if (strpos($subfolder, '..') !== false)
					{
						JError::raiseError(20, 'ARS - Use of relative paths not permitted'); // don't translate
						jexit();
					}
					$subfolder = trim($subfolder, '/');

					$folderWithoutS3Prefix = substr($folder, 5);

					if ($subfolder == $folderWithoutS3Prefix)
					{
						$subfolder = '';
					}
					elseif (strpos($subfolder, $folderWithoutS3Prefix) === 0)
					{
						$subfolder = substr($subfolder, strlen($folderWithoutS3Prefix));
					}

					$folder = $folder . (empty($subfolder) ? '' : '/' . $subfolder);

					$pieces = explode('/', $subfolder);
					array_pop($pieces);
					$parent = implode('/', $pieces);
				}
				else
				{
					// Clean and check subfolder
					$subfolder = JPath::clean($subfolder);
					if (strpos($subfolder, '..') !== false)
					{
						JError::raiseError(20, 'ARS - Use of relative paths not permitted'); // don't translate
						jexit();
					}
					// Find the parent path to our subfolder
					$parent = JPath::clean(@realpath($folder . '/' . $subfolder . '/..'));
					$parent = trim(str_replace(JPath::clean($folder), '', $parent), '/\\');
					$folder = JPath::clean($folder . '/' . $subfolder);
				}

				// Calculate the full path to the subfolder
				$this->setState('parent', $parent);
				$this->setState('folder', $subfolder);
			}
			else
			{
				if ($useS3)
				{
					$this->setState('parent', null);
					$this->setState('folder', $folder);
				}
				else
				{
					$this->setState('parent', null);
					$this->setState('folder', '');
				}
			}
		}

		return $folder;
	}

	function getFiles()
	{
		$files = array();
		$folder = $this->getCategoryFolder();
		if (empty($folder))
		{
			return $files;
		}

		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';

		if ($useS3)
		{
			$everything = $this->_listS3Contents($folder);
			$folder = trim(substr($folder, 5), '/');
			$dirLength = strlen($folder);
			if (count($everything))
			{
				foreach ($everything as $path => $info)
				{
					if (array_key_exists('size', $info) && (substr($path, -1) != '/'))
					{
						if (substr($path, 0, $dirLength) == $folder)
						{
							$path = substr($path, $dirLength);
						}
						$path = trim($path, '/');
						$files[] = array(
							'filename' => $path,
							'size'     => $info['size']
						);
					}
				}
			}
		}
		else
		{
			JLoader::import('joomla.filesystem.folder');
			$temp = JFolder::files($folder);
			if (!empty($temp))
			{
				foreach ($temp as $file)
				{
					$files[] = array(
						'filename' => $file,
						'size'     => @filesize($folder . '/' . $file)
					);
				}
			}
		}

		return $files;
	}

	function getFolders()
	{
		$folders = array();
		$folder = $this->getCategoryFolder();
		if (empty($folder))
		{
			return $folders;
		}

		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';

		if ($useS3)
		{
			$everything = $this->_listS3Contents($folder);
			$folder = trim(substr($folder, 5), '/');
			$dirLength = strlen($folder);
			if (count($everything))
			{
				foreach ($everything as $path => $info)
				{
					if (!array_key_exists('size', $info) && (substr($path, -1) == '/'))
					{
						if (substr($path, 0, $dirLength) == $folder)
						{
							$path = substr($path, $dirLength);
						}
						$path = trim($path, '/');
						$folders[] = $path;
					}
				}
			}
		}
		else
		{
			JLoader::import('joomla.filesystem.folder');
			$folders = JFolder::folders($folder);
		}

		return $folders;
	}

	private function _listS3Contents($path = null)
	{
		static $lastDirectory = null;
		static $lastListing = array();

		$directory = substr($path, 5);
		if ($directory === false)
		{
			$directory = '/';
		}

		if ($lastDirectory != $directory)
		{
			if ($directory == '/')
			{
				$directory = null;
			}
			else
			{
				$directory = trim($directory, '/') . '/';
			}

			$s3 = ArsHelperAmazons3::getInstance();
			$lastListing = $s3->getBucket('', $directory, null, null, '/', true);

			$lastDirectory = $directory;
		}

		return $lastListing;
	}

	function delete()
	{
		$folder = $this->getCategoryFolder();
		$file = $this->getState('file', '');
		if (empty($file))
		{
			return '';
		}

		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3 = $potentialPrefix == 's3://';

		if ($useS3)
		{
			$folder = trim(substr($folder, 5), '/');
			if (!empty($folder))
			{
				$folder .= '/';
			}
			$filepath = $folder . $file;
		}
		else
		{
			$filepath = $folder . '/' . $file;
		}

		if (!$useS3)
		{
			JLoader::import('joomla.filesystem.file');
			if (!JFile::exists($filepath))
			{
				return false;
			}
		}

		$files = F0FModel::getTmpInstance('Items', 'ArsModel')
						 ->category($this->getState('category', 0))
						 ->filename($this->getState('file', ''))
						 ->getItemList();

		if (!empty($files))
		{
			// Unpublish entries
			foreach ($files as $entry)
			{
				$item = F0FModel::getTmpInstance('Items', 'ArsModel')
								->getItem($entry->id);
				$item->save(array(
					'published' => 0
				));
			}
		}

		if ($useS3)
		{
			$s3 = ArsHelperAmazons3::getInstance();

			return $s3->deleteObject($filepath);
		}
		else
		{
			return JFile::delete($filepath);
		}
	}
}