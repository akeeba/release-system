<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsModelDownloads extends F0FModel
{
	/** @var   boolean  True if we have logged in a user */
	protected $haveLoggedInAUser = false;

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
		// Initialise
		$this->item = null;
		$null       = null;

		$items = F0FModel::getTmpInstance('Items','ArsModel')
			->access_user(JFactory::getUser()->id)
			->published(1)
			->item_id($id)
			->getItemList();

		if (empty($items))
		{
			return $null;
		}

		// If the user is a guest and I wanted to show it to him, tell the controller to fire the redirect
		if(JFactory::getUser()->guest && $items[0]->show_unauth_links && $items[0]->cat_show_unauth && $items[0]->rel_show_unauth)
		{
			$return = -1;
			return $return;
		}

		// Additional check on item access level. I removed that from the model to allow
		// item listing to unauthorized users
		if(!in_array($items[0]->access, JFactory::getUser()->getAuthorisedViewLevels()))
		{
			return $null;
		}

		// Does it pass the access level / subscriptions filter?
		$dummy = ArsHelperFilter::filterList( $items );
		if(!count($dummy))
		{
			return $null;
		}

		$item = F0FModel::getTmpInstance('Items','ArsModel')->getTable();
		$item->bind(array_pop($items));

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

			$innerQuery = $db->getQuery(true)
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

			$query = $db->getQuery(true)
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

			JLoader::import('joomla.filesystem.folder');
			JLoader::import('joomla.filesystem.file');

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
				$this->logoutUser();
				JFactory::getApplication()->close();
			}

			if(!JFolder::exists($folder)) {
				$folder = JPATH_ROOT.'/'.$folder;
				if(!JFolder::exists($folder)) {
					header('HTTP/1.0 404 Not Found');
					$this->logoutUser();
					exit(0);
				}
			}

			$filename = $folder.'/'.$item->filename;
			if(!JFile::exists($filename)) {
				header('HTTP/1.0 404 Not Found');
				$this->logoutUser();
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
            JLoader::import('joomla.plugin.helper');
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
			header('Connection: close');

			error_reporting(0);
        	if ( ! ini_get('safe_mode') ) {
		    	set_time_limit(0);
        	}

			// Support resumable downloads
			$isResumable = false;
			$seek_start = 0;
			$seek_end = $filesize - 1;
			if(isset($_SERVER['HTTP_RANGE'])) {
				list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

				if ($size_unit == 'bytes') {
					//multiple ranges could be specified at the same time, but for simplicity only serve the first range
					//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
					list($range, $extra_ranges) = explode(',', $range_orig, 2);
				} else {
					$range = '';
				}
			} else {
				$range = '';
			}

			if($range) {
				//figure out download piece from range (if set)
				list($seek_start, $seek_end) = explode('-', $range, 2);

				//set start and end based on range (if set), else set defaults
				//also check for invalid ranges.
				$seek_end = (empty($seek_end)) ? ($filesize - 1) : min(abs(intval($seek_end)),($filesize - 1));
				$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

				$isResumable = true;
			}

			// Use 1M chunks for echoing the data to the browser
			$chunksize = 1024*1024; //1M chunks
			$buffer = '';
	   		$handle = @fopen($filename, 'rb');
	   		if($handle !== false)
	   		{

				if($isResumable) {
					//Only send partial content header if downloading a piece of the file (IE workaround)
					if ($seek_start > 0 || $seek_end < ($filesize - 1)) {
						header('HTTP/1.1 206 Partial Content');
					}

					// Necessary headers
					$totalLength = $seek_end - $seek_start + 1;
					header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$filesize);
					header('Content-Length: '.$totalLength);

					// Seek to start
					fseek($handle, $seek_start);
				} else {
					$isResumable = false;
					// Notify of filesize, if this info is available
					if($filesize > 0) header('Content-Length: '.(int)$filesize);
				}
				$read = 0;
	   			while (!feof($handle) && ($chunksize > 0)) {
					if($isResumable) {
						if($totalLength - $read < $chunksize) {
							$chunksize = $totalLength - $read;
							if($chunksize < 0) continue;
						}
					}
	   				$buffer = fread($handle, $chunksize);
					if($isResumable) {
						$read += strlen($buffer);
					}
	   				echo $buffer;
	   				@ob_flush();
	   				flush();
	   			}
	   			@fclose($handle);
	   		}
	   		else
	   		{
				// Notify of filesize, if this info is available
				if($filesize > 0) header('Content-Length: '.(int)$filesize);
	   			@readfile($filename);
	   		}

            // Call any plugins to post-process the file download
            $object = array(
            	'rawentry'		=> $item,
            	'filename'		=> $filename,
            	'basename'		=> $basename,
            	'header_file'	=> $header_file,
            	'mimetype'		=> $mime_type,
            	'filesize'		=> $filesize,
				'resumable'		=> $isResumable,
				'range_start'	=> $seek_start,
				'range_end'		=> $seek_end,
            );
            $app = JFactory::getApplication();
            $ret = $app->triggerEvent('onARSAfterSendFile', array($object));
            if(!empty($ret)) {
            	foreach($ret as $r) {
            		echo $r;
            	}
            }

		}

		$this->logoutUser();
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

	/**
	 * Log in a user if necessary
	 *
	 * @return  boolean  True if a user was logged in
	 */
	public function loginUser()
	{
		// No need to log in a user if the user is already logged in
		if (!JFactory::getUser()->guest)
		{
			return false;
		}

		// This helper contains some useful dlid functions and stuff...
		require_once JPATH_SITE . '/components/com_ars/helpers/filter.php';

		// This is Joomla!'s login and user helpers
		JPluginHelper::importPlugin('user');
		JLoader::import('joomla.user.helper');

		// Get the query parameters
		$dlid						= JRequest::getString('dlid',null);
		$credentials				= array();
		$credentials['username']	= JRequest::getVar('username', '', 'get', 'username');
		$credentials['password']	= JRequest::getString('password', '', 'get', JREQUEST_ALLOWRAW);

		// Initialise
		$user_id = 0;

		// First attempt to log in by download ID
		if (!empty($dlid)) {
			try
			{
				$user_id = ArsHelperFilter::getUserFromDownloadID($dlid)->id;
			}
			catch (Exception $exc)
			{
				$user_id = 0;
			}
		}

		// If the dlid failed, used he legacy username/password pair
		if (($user_id === 0) && !empty($credentials['username']) && !empty($credentials['password']) )
		{
			JLoader::import( 'joomla.user.authentication');
			$app = JFactory::getApplication();
			$options = array('remember' => false);
			$authenticate = JAuthentication::getInstance();
			$response	  = $authenticate->authenticate($credentials, $options);

			if ($response->status == JAuthentication::STATUS_SUCCESS)
			{
				$user_id = JUserHelper::getUserId($response->username);
			}
		}

		// Log in the user
		if ($user_id !== 0)
		{
			// Mark the user login so we can log him out later on
			$this->haveLoggedInAUser = true;

			// This line returns an empty JUser object
			$newUserObject = new JUser();
			// This line FORCE RELOADS the user record.
			$newUserObject->load($user_id);

			// Mark the user as logged in
			$newUserObject->block = 0;
			$newUserObject->set('guest', 0);

			// Register the needed session variables
			$session = JFactory::getSession();
			$session->set('user', $newUserObject);

			$db = JFactory::getDBO();

			// Check to see the the session already exists.
			$app = JFactory::getApplication();
			$app->checkSession();

			// Update the user related fields for the Joomla sessions table.
			$query = $db->getQuery(true)
				->update($db->qn('#__session'))
				->set(array(
					$db->qn('guest').' = ' . $db->q($newUserObject->get('guest')),
					$db->qn('username').' = ' . $db->q($newUserObject->get('username')),
					$db->qn('userid').' = ' . (int) $newUserObject->get('id')
				))->where($db->qn('session_id').' = '.$db->q($session->getId()));
			$db->setQuery($query);
			$db->execute();

			// Hit the user last visit field
			$newUserObject->setLastVisit();
		}

		return $this->haveLoggedInAUser;
	}

	/**
	 * Log out the user who was logged in with the loginUser() method above
	 *
	 * @return  boolean  True if a user was logged out
	 */
	public function logoutUser()
	{
		if (!$this->haveLoggedInAUser)
		{
			return false;
		}

		$my 		= JFactory::getUser();
		$session 	= JFactory::getSession();
		$app 		= JFactory::getApplication();

		// Hit the user last visit field
		$my->setLastVisit();

		// Destroy the php session for this user
		$session->destroy();

		// Force logout all users with that userid
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->delete($db->qn('#__session'))
			->where($db->qn('userid').' = '.(int) $my->id)
			->where($db->qn('client_id').' = '.(int) $app->getClientId());
		$db->setQuery($query);
		$db->execute();

		return $this->haveLoggedInAUser;
	}
}