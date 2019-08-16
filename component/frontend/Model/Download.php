<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use FOF30\Model\Model;
use Joomla\CMS\Authentication\Authentication as JAuthentication;
use Joomla\CMS\Authentication\AuthenticationResponse as JAuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserHelper;

class Download extends Model
{
	/**
	 * True if we have logged in a user
	 *
	 * @var  bool
	 */
	protected $haveLoggedInAUser = false;

	public function doDownload(Items $item): void
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

		$folder = $item->release->category->directory;

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
			$fInfo     = new \finfo(FILEINFO_MIME_TYPE);
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
		PluginHelper::importPlugin('ars');

		// Call any plugins to post-process the download file parameters
		$object = [
			'rawentry'    => $item,
			'filename'    => $filename,
			'basename'    => $basename,
			'header_file' => $header_file,
			'mimetype'    => $mime_type,
			'filesize'    => $filesize,
		];

		$app      = Factory::getApplication();
		$retArray = $app->triggerEvent('onARSBeforeSendFile', [$object]);

		if (!empty($retArray))
		{
			foreach ($retArray as $ret)
			{
				if (empty($ret) || !is_array($ret))
				{
					continue;
				}

				$ret         = (object) $ret;
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
					header('Content-Length: ' . (int) $filesize);
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
				header('Content-Length: ' . (int) $filesize);
			}

			@readfile($filename);
		}

		// Call any plugins to post-process the file download
		$object = [
			'rawentry'    => $item,
			'filename'    => $filename,
			'basename'    => $basename,
			'header_file' => $header_file,
			'mimetype'    => $mime_type,
			'filesize'    => $filesize,
			'resumable'   => $isResumable,
			'range_start' => $seek_start,
			'range_end'   => $seek_end,
		];

		$ret = $app->triggerEvent('onARSAfterSendFile', [$object]);

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

	private function get_mime_type(string $filename): string
	{
		$type = @mime_content_type($filename);

		if ($type === false)
		{
			$type = 'application/octet-stream';
		}

		return $type;
	}

	/**
	 * Log in a user if necessary
	 *
	 * @return  boolean  True if a user was logged in
	 */
	public function loginUser(): bool
	{
		// No need to log in a user if the user is already logged in
		if (!$this->container->platform->getUser()->guest)
		{
			return false;
		}

		$dlid = Filter::reformatDownloadID($this->input->getString('dlid', ''));

		if (empty($dlid))
		{
			$this->haveLoggedInAUser = false;

			return false;
		}

		try
		{
			$user_id = Filter::getUserFromDownloadID($dlid)->id;
		}
		catch (\Exception $exc)
		{
			$user_id = 0;
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
		$options            = ['remember' => false];
		$response           = new JAuthenticationResponse;
		$response->status   = JAuthentication::STATUS_SUCCESS;
		$response->type     = 'downloadid';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		$this->container->platform->importPlugin('user');
		$this->container->platform->runPlugins('onLoginUser', [(array) $response, $options]);

		// Set the user in the session, effectively logging in the user
		$userid = UserHelper::getUserId($response->username);
		$user   = $this->container->platform->getUser($userid);

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
	public function logoutUser(): bool
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
