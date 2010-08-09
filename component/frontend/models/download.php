<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once dirname(__FILE__).DS.'base.php';

class ArsModelDownload extends ArsModelBaseFE
{
	/**
	 * Loads and returns an item definition
	 * @param int $id The Item ID to load
	 * @return TableItems|null An instance of TableItems, or null if the user shouldn't view the item
	 */
	public function getItem($id = 0)
	{
		$this->item = null;

		$model = JModel::getInstance('Items','ArsModel');
		$model->reset();
		$model->setId($id);
		$item = $model->getItem();

		// Is it published?
		if(!$item->published) return null;

		// Does it pass the access level / AMBRA.subs filter?
		$dummy = $this->filterList( array($item) );
		if(!count($dummy)) return null;

		$this->item = $item;
		return $item;
	}

	public function doDownload()
	{
		if($this->item->type == 'link')
		{
			if(ob_get_length () !== FALSE) {
				@ob_end_clean();
			}
			header('Location: '.$this->item->url);
		}
		else
		{
			$db = $this->getDBO();
			$sql = 'SELECT * FROM `#__ars_view_items` WHERE `id` = '.$this->item->id;
			$db->setQuery($sql);
			$item = $db->loadObject();

			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');

			$folder = $item->cat_directory;
			if(!JFolder::exists($folder)) {
				$folder = JPATH_ROOT.DS.$folder;
				if(!JFolder::exists($folder)) {
					header('HTTP/1.0 404 Not Found');
					exit(0);
				}
			}

			$filename = $folder.DS.$item->filename;
			if(!JFile::exists($filename)) {
				header('HTTP/1.0 404 Not Found');
				exit(0);
			}

			$basename = $item->alias;
			if(empty($basename)) {
				$basename = @basename($filename);
			}
			$filesize = @filesize($filename);
			$mime_type = $this->get_mime_type($filename);
			if(empty($mime_type)) $mime_type = 'application/octet-stream';

			JRequest::setVar('format','raw');
			@ob_end_clean();
			@clearstatcache();
			// Send MIME headers
			header('MIME-Version: 1.0');
			header('Content-Disposition: attachment; filename='.$basename);
			header('Content-Transfer-Encoding: binary');
			header('Content-Type: '.$mime_type);
			// Notify of filesize, if this info is available
			if($filesize > 0) header('Content-Length: '.@filesize($filename));
			// Disable caching
			header('Expires: Mon, 20 Dec 1998 01:00:00 GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			if($filesize > 0)
			{
				// If the filesize is reported, use 2M chunks for echoing the data to the browser
				$blocksize = (2 << 20); //2M chunks
				$handle    = @fopen($filename, "r");
				// Now we need to loop through the file and echo out chunks of file data
				if($handle !== false) while(!@feof($handle)){
				    echo @fread($handle, $blocksize);
				}
			} else {
				// If the filesize is not reported, hope that readfile works
				@readfile($filename);
			}
		}
		exit(0);
	}

	private function get_mime_type($filename) {
		$mimePath = JPATH_COMPONENT_ADMINISTRATOR.DS.'assets'.DS.'mime';
		$fileext = substr(strrchr($filename, '.'), 1);
		if (empty($fileext)) return (false);
		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file($mimePath.DS."mime.types");
		foreach($lines as $line) {
			if (substr($line, 0, 1) == '#') continue; // skip comments
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches)) continue; // no match to the extension
			return ($matches[1]);
		}
		return (false); // no match at all
	}
}