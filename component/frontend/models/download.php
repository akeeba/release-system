<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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

	public function antiLeech()
	{
		$myURI = JURI::getInstance();
		$myURI->setPath('');
		$myURI->setQuery('');
		$me = $myURI->toString();

		$referer = JRequest::getVar('HTTP_REFERER','','SERVER');

		$check = substr($referer,0,strlen($me));
		if($check != $me)
		{
			return JError::raiseError(403, 'Anti-leech protection triggered' );
		}
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
			$escid = $db->Quote($this->item->id);
			$sql = <<<ENDSQL
SELECT
    `i`.*,
    `r`.`category_id`, `r`.`version`, `r`.`alias` as `rel_alias`,
    `maturity`, `r`.`groups` as `rel_groups`, `r`.`access` as `rel_access`,
    `r`.`published` as `rel_published`,
    `cat_title`, `cat_alias`, `cat_type`, `cat_groups`,
    `cat_directory`, `cat_access`, `cat_published`
FROM
    `#__ars_items` as `i`
    INNER JOIN (
SELECT
    `r`.*, `c`.`title` as `cat_title`, `c`.`alias` as `cat_alias`,
    `c`.`type` as `cat_type`, `c`.`groups` as `cat_groups`,
    `c`.`directory` as `cat_directory`, `c`.`access` as `cat_access`,
    `c`.`published` as `cat_published`
FROM
    `#__ars_releases` AS `r`
    INNER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `r`.`category_id`)
) AS `r` ON(`r`.`id` = `i`.`release_id`)
WHERE `i`.`id` = $escid

ENDSQL;
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

			$basename = @basename($filename);
			$filesize = @filesize($filename);
			$mime_type = $this->get_mime_type($filename);
			if(empty($mime_type)) $mime_type = 'application/octet-stream';

			JRequest::setVar('format','raw');

			// Clear cache
			while (@ob_end_clean());

            // Fix IE bugs
            if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            	$header_file = preg_replace('/\./', '%2e', $basename, substr_count($basename, '.') - 1);

            	if (ini_get('zlib.output_compression'))  {
					ini_set('zlib.output_compression', 'Off');
				}
            }
            else {
            	$header_file = $basename;
            }
            
            // Import ARS plugins
            jimport('joomla.plugin.helper');
            JPluginHelper::importPlugin('ars');
            
            // Call any plugins to post-process the download file parameters
            $object = array(
            	'rawentry'		=> $item,
            	'filename'		=> $filename,
            	'basename'		=> $basename,
            	'header_file'	=> $header_file,
            	'mimetype'		=> $mime_type,
            	'filesize'		=> $filesize
            );
            $app = JFactory::getApplication();
            $retArray = $app->triggerEvent('onARSBeforeSendFile', array($object));
            if(!empty($retArray)) {
            	foreach($retArray as $ret)
            	{
            		if(empty($ret) || !is_array($ret)) continue;
            		$ret = (object)$ret;
	            	$filename = $ret->filename;
	            	$basename = $ret->basename;
	            	$header_file = $ret->header_file;
	            	$mime_type = $ret->mimetype;
	            	$filesize = $ret->filesize;
            	}
            }

			@clearstatcache();
			// Disable caching
			header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public", false);

			// Send MIME headers
            header("Content-Description: File Transfer");
			header('Content-Type: '.$mime_type);
            header("Accept-Ranges: bytes");
			header('Content-Disposition: attachment; filename='.$header_file);
			header('Content-Transfer-Encoding: binary');
			// Notify of filesize, if this info is available
			if($filesize > 0) header('Content-Length: '.(int)$filesize);

			error_reporting(0);
        	if ( ! ini_get('safe_mode') ) {
		    	set_time_limit(0);
        	}

			// Use 1M chunks for echoing the data to the browser
			$chunksize = 1024*1024; //1M chunks
			$buffer = '';
	   		$handle = @fopen($filename, 'rb');
	   		if($handle !== false)
	   		{
	   			while (!feof($handle)) {
	   				$buffer = fread($handle, $chunksize);
	   				echo $buffer;
	   				@ob_flush();
	   				flush();
	   			}
	   			@fclose($handle);
	   		}
	   		else
	   		{
	   			@readfile($filename);
	   		}
	   		
            // Call any plugins to post-process the file download
            $object = array(
            	'rawentry'		=> $item,
            	'filename'		=> $filename,
            	'basename'		=> $basename,
            	'header_file'	=> $header_file,
            	'mimetype'		=> $mime_type,
            	'filesize'		=> $filesize
            );
            $app = JFactory::getApplication();
            $ret = $app->triggerEvent('onARSAfterSendFile', array($object));
            if(!empty($ret)) {
            	foreach($ret as $r) {
            		echo $r;
            	}
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
		return 'application/octet-stream'; // no match at all
	}
}