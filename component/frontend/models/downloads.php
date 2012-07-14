<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelDownloads extends FOFModel
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/amazons3.php';
		require_once JPATH_SITE.'/components/com_ars/helpers/filter.php';
	}
	
	/**
	 * Loads and returns an item definition
	 * @param int $id The Item ID to load
	 * @return TableItems|null An instance of TableItems, or null if the user shouldn't view the item
	 */
	public function &getItem($id = null)
	{
		$this->item = null;

		$item = FOFModel::getTmpInstance('Items','ArsModel')
			->getItem($id);

		// Is it published?
		if(!$item->published) {
			return null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = ArsHelperFilter::filterList( array($item) );
		if(!count($dummy)) {
			return null;
		}

		$this->item = $item;
		return $item;
	}

	public function doDownload()
	{
		if($this->item->type == 'link')
		{
			if(@ob_get_length () !== FALSE) {
				@ob_end_clean();
			}
			header('Location: '.$this->item->url);
		}
		else
		{
			$db = $this->getDBO();
			
			$innerQuery = FOFQueryAbstract::getNew($db)
				->select(array(
					$db->qn('r').'.'.'*',
					$db->qn('c').'.'.$db->qn('title').' AS '.$db->qn('cat_title'),
					$db->qn('c').'.'.$db->qn('alias').' AS '.$db->qn('cat_alias'),
					$db->qn('c').'.'.$db->qn('type').' AS '.$db->qn('cat_type'),
					$db->qn('c').'.'.$db->qn('groups').' AS '.$db->qn('cat_groups'),
					$db->qn('c').'.'.$db->qn('directory').' AS '.$db->qn('cat_directory'),
					$db->qn('c').'.'.$db->qn('access').' AS '.$db->qn('cat_access'),
					$db->qn('c').'.'.$db->qn('published').' AS '.$db->qn('cat_published'),
				))
				->from($db->qn('#__ars_releases').' AS '.$db->qn('r'))
				->join('INNER',$db->qn('#__ars_categories').' AS '.$db->qn('c').' ON ('.
					$db->qn('c').'.'.$db->qn('id').' = '.$db->qn('r').'.'.$db->qn('category_id')
				.')')
			;
			
			$query = FOFQueryAbstract::getNew($db)
				->select(array(
					$db->qn('i').'.'.'*',
					$db->qn('r').'.'.$db->qn('category_id'),
					$db->qn('r').'.'.$db->qn('version'),
					$db->qn('r').'.'.$db->qn('alias').' AS '.$db->qn('rel_alias'),
					$db->qn('maturity'),
					$db->qn('r').'.'.$db->qn('groups').' AS '.$db->qn('rel_groups'),
					$db->qn('r').'.'.$db->qn('access').' AS '.$db->qn('rel_access'),
					$db->qn('r').'.'.$db->qn('published').' AS '.$db->qn('rel_published'),
					$db->qn('cat_title'), $db->qn('cat_alias'), $db->qn('cat_type'),
					$db->qn('cat_groups'), $db->qn('cat_directory'), $db->qn('cat_access'),
					$db->qn('cat_published')
				))->from($db->qn('#__ars_items').' AS '.$db->qn('i'))
				->join('INNER', '('.$innerQuery.') AS '.$db->qn('r').' ON('.
						$db->qn('r').'.'.$db->qn('id').' = '.$db->qn('i').'.'.$db->qn('release_id').')')
				->where($db->qn('i').'.'.$db->qn('id').' = '.$db->q($this->item->id))
			;
			
			$db->setQuery($query);
			$item = $db->loadObject();

			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');

			$folder = $item->cat_directory;
			
			$potentialPrefix = substr($folder, 0, 5);
			$potentialPrefix = strtolower($potentialPrefix);
			$useS3 = $potentialPrefix == 's3://';
			
			if($useS3) {
				$filename = substr($folder,5).'/'.$item->filename;
				$s3 = ArsHelperAmazons3::getInstance();
				$url = $s3->getAuthenticatedURL('', $filename);
				
				if(@ob_get_length () !== FALSE) {
					@ob_end_clean();
				}
				header('Location: '.$url);
				JFactory::getApplication()->close();
			}
			
			if(!JFolder::exists($folder)) {
				$folder = JPATH_ROOT.'/'.$folder;
				if(!JFolder::exists($folder)) {
					header('HTTP/1.0 404 Not Found');
					exit(0);
				}
			}

			$filename = $folder.'/'.$item->filename;
			if(!JFile::exists($filename)) {
				header('HTTP/1.0 404 Not Found');
				exit(0);
			}

			$basename = @basename($filename);
			$filesize = @filesize($filename);
			$mime_type = $this->get_mime_type($filename);
			if(empty($mime_type)) $mime_type = 'application/octet-stream';

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
			header('Content-Disposition: attachment; filename="'.$header_file.'"');
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
		
		JFactory::getApplication()->close();
	}

	private function get_mime_type($filename) {
		$mimePath = JPATH_ADMINISTRATOR.'/components/com_ars/assets/mime';
		$fileext = substr(strrchr($filename, '.'), 1);
		if (empty($fileext)) return (false);
		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file($mimePath."/mime.types");
		foreach($lines as $line) {
			if (substr($line, 0, 1) == '#') continue; // skip comments
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches)) continue; // no match to the extension
			return ($matches[1]);
		}
		return 'application/octet-stream'; // no match at all
	}
}