<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Admin\Helper\AmazonS3;
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use FOF30\Model\Model;
use JAuthentication;
use JAuthenticationResponse;
use JLoader;

class Download extends Model
{
	/**
	 * True if we have logged in a user
	 *
	 * @var  bool
	 */
	protected $haveLoggedInAUser = false;

	public function doDownload(Items $item)
	{
		// If it's a link we just have to redirect users
		if ($item->type == 'link')
		{
			if (@ob_get_length() !== false)
			{
				@ob_end_clean();
			}

			$this->logoutUser();
			$this->container->platform->redirect($item->url);

			return;
		}

		\JLoader::import('joomla.filesystem.folder');
		\JLoader::import('joomla.filesystem.file');

		$folder = $item->release->category->directory;

		$potentialPrefix = substr($folder, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);
		$useS3           = $potentialPrefix == 's3://';

		if ($useS3)
		{
			$filename = substr($folder, 5) . '/' . $item->filename;
			$s3       = AmazonS3::getInstance();
			$url      = $s3->getAuthenticatedURL($filename);

			if (@ob_get_length() !== false)
			{
				@ob_end_clean();
			}

			$this->logoutUser();
			$this->container->platform->redirect($url);

			return;

		}

		if (!\JFolder::exists($folder))
		{
			$folder = JPATH_ROOT . '/' . $folder;

			if (!\JFolder::exists($folder))
			{
				$this->logoutUser();

				throw new \RuntimeException('Not found', 404);
			}
		}

		$filename = $folder . '/' . $item->filename;

		if (!\JFile::exists($filename))
		{
			$this->logoutUser();

			throw new \RuntimeException('Not found', 404);
		}

		$basename  = @basename($filename);
		$filesize  = @filesize($filename);
		$mime_type = null;

		if (class_exists('finfo'))
		{
			$fInfo = new \finfo(FILEINFO_MIME_TYPE);
			$mime_type = $fInfo->file($filename);
		}

		if (empty($mime_type))
		{
			$mime_type = $this->get_mime_type($filename);
		}

		if (empty($mime_type))
		{
			$mime_type = 'application/octet-stream';
		}

		// Clear cache
		while (@ob_end_clean())
		{
			// Make sure no junk will come before out content – to the extent we have a say on this...
		}

		// Fix IE bugs
		if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
		{
			$header_file = preg_replace('/\./', '%2e', $basename, substr_count($basename, '.') - 1);

			if (ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}
		else
		{
			$header_file = $basename;
		}

		// Import ARS plugins
		\JLoader::import('joomla.plugin.helper');
		\JPluginHelper::importPlugin('ars');

		// Call any plugins to post-process the download file parameters
		$object = array(
			'rawentry'    => $item,
			'filename'    => $filename,
			'basename'    => $basename,
			'header_file' => $header_file,
			'mimetype'    => $mime_type,
			'filesize'    => $filesize
		);

		$app      = \JFactory::getApplication();
		$retArray = $app->triggerEvent('onARSBeforeSendFile', array($object));

		if (!empty($retArray))
		{
			foreach ($retArray as $ret)
			{
				if (empty($ret) || !is_array($ret))
				{
					continue;
				}

				$ret         = (object)$ret;
				$filename    = $ret->filename;
				$basename    = $ret->basename;
				$header_file = $ret->header_file;
				$mime_type   = $ret->mimetype;
				$filesize    = $ret->filesize;
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
		header('Content-Type: ' . $mime_type);
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="' . $header_file . '"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');

		error_reporting(0);

		if (!ini_get('safe_mode'))
		{
			set_time_limit(0);
		}

		// Support resumable downloads
		$isResumable = false;
		$seek_start  = 0;
		$seek_end    = $filesize - 1;

		if (isset($_SERVER['HTTP_RANGE']))
		{
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

			if ($size_unit == 'bytes')
			{
				//multiple ranges could be specified at the same time, but for simplicity only serve the first range
				//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
			}
			else
			{
				$range = '';
			}
		}
		else
		{
			$range = '';
		}

		if ($range)
		{
			// Figure out download piece from range (if set)
			list($seek_start, $seek_end) = explode('-', $range, 2);

			// Set start and end based on range (if set), else set defaults. Also checks for invalid ranges.
			$seek_end   = (empty($seek_end)) ? ($filesize - 1) : min(abs(intval($seek_end)), ($filesize - 1));
			$seek_start =
				(empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

			$isResumable = true;
		}

		// Use 1M chunks for echoing the data to the browser
		$chunksize = 1024 * 1024; //1M chunks
		$handle    = @fopen($filename, 'rb');

		if ($handle !== false)
		{

			if ($isResumable)
			{
				//Only send partial content header if downloading a piece of the file (IE workaround)
				if ($seek_start > 0 || $seek_end < ($filesize - 1))
				{
					header('HTTP/1.1 206 Partial Content');
				}

				// Necessary headers
				$totalLength = $seek_end - $seek_start + 1;

				header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $filesize);
				header('Content-Length: ' . $totalLength);

				// Seek to start
				fseek($handle, $seek_start);
			}
			else
			{
				$isResumable = false;

				// Notify of filesize, if this info is available
				if ($filesize > 0)
				{
					header('Content-Length: ' . (int)$filesize);
				}
			}

			$read = 0;

			while (!feof($handle) && ($chunksize > 0))
			{
				if ($isResumable)
				{
					if ($totalLength - $read < $chunksize)
					{
						$chunksize = $totalLength - $read;

						if ($chunksize < 0)
						{
							continue;
						}
					}
				}

				$buffer = fread($handle, $chunksize);

				if ($isResumable)
				{
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
			if ($filesize > 0)
			{
				header('Content-Length: ' . (int)$filesize);
			}

			@readfile($filename);
		}

		// Call any plugins to post-process the file download
		$object = array(
			'rawentry'    => $item,
			'filename'    => $filename,
			'basename'    => $basename,
			'header_file' => $header_file,
			'mimetype'    => $mime_type,
			'filesize'    => $filesize,
			'resumable'   => $isResumable,
			'range_start' => $seek_start,
			'range_end'   => $seek_end,
		);

		$ret = $app->triggerEvent('onARSAfterSendFile', array($object));

		if (!empty($ret))
		{
			foreach ($ret as $r)
			{
				if (!empty($r))
				{
					echo $r;
				}
			}
		}

		$this->logoutUser();
	}

	private function get_mime_type($filename)
	{
		$mimePath = JPATH_ADMINISTRATOR . '/components/com_ars/assets/mime';
		$fileext  = substr(strrchr($filename, '.'), 1);

		if (empty($fileext))
		{
			return (false);
		}

		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file($mimePath . "/mime.types");

		foreach ($lines as $line)
		{
			// Skip comments
			if (substr($line, 0, 1) == '#')
			{
				continue;
			}

			$line = rtrim($line) . " ";

			// No match to the extension?
			if (!preg_match($regex, $line, $matches))
			{
				continue;
			}

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
		if (!$this->container->platform->getUser()->guest)
		{
			return false;
		}

		// This is Joomla!'s login and user helpers
		\JPluginHelper::importPlugin('user');
		JLoader::import('joomla.user.helper');

		// Get the query parameters
		$dlid                    = $this->input->getString('dlid', null);
		$credentials             = array();
		$credentials['username'] = $this->input->getUsername('username', '');
		$credentials['password'] = $this->input->get('password', '', 'raw', 3);

		// Initialise
		$user_id = 0;

		// First attempt to log in by download ID
		if (!empty($dlid))
		{
			try
			{
				$user_id = Filter::getUserFromDownloadID($dlid)->id;
			}
			catch (\Exception $exc)
			{
				$user_id = 0;
			}
		}

		if ($user_id == 0)
		{
			$this->haveLoggedInAUser = false;

			return false;
		}

		// Log in the user
		$user = $this->container->platform->getUser($user_id);

		// Mark the user login so we can log him out later on
		$this->haveLoggedInAUser = true;

		// Get a fake login response
		\JLoader::import('joomla.user.authentication');
		$options = array('remember' => false);
		$response = new JAuthenticationResponse;
		$response->status = JAuthentication::STATUS_SUCCESS;
		$response->type = 'downloadid';
		$response->username = $user->username;
		$response->email= $user->email;
		$response->fullname= $user->name;

		// Run the login user events
		$this->container->platform->importPlugin('user');
		$results = $this->container->platform->runPlugins('onLoginUser', array((array)$response, $options));

		unset($results); // Just to make phpStorm happy

		// Set the user in the session, effectively logging in the user
		\JLoader::import('joomla.user.helper');
		$userid = \JUserHelper::getUserId($response->username);
		$user = $this->container->platform->getUser($userid);

		$this->container->platform->setSessionVar('user', $user);

		// Update the user's last visit time in the database
		$user->setLastVisit(time());
		$user->save();

		return true;
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

		$haveLoggedOut = $this->container->platform->logoutUser();

		$this->haveLoggedInAUser = !$haveLoggedOut;

		return $haveLoggedOut;
	}
}
