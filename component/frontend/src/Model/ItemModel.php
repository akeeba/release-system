<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use finfo;
use Joomla\Application\Web\WebClient;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

#[\AllowDynamicProperties]
class ItemModel extends BaseDatabaseModel
{
	use TableAssertionTrait;

	private const CHUNK_SIZE = 1048576;

	/**
	 * True if we have logged in a user
	 *
	 * @var  bool
	 */
	protected $haveLoggedInAUser = false;

	/**
	 * Make sure the download item is a URL or a file which does exist.
	 *
	 * @param   ItemTable      $item      The item record to download
	 * @param   CategoryTable  $category  The category the item belongs to
	 *
	 * @return  void
	 */
	public function preDownloadCheck(ItemTable $item, CategoryTable $category): void
	{
		// If it's a link we just have to redirect users
		if ($item->type == 'link')
		{
			return;
		}

		try
		{
			$folder = $category->directory;

			if (!Folder::exists($folder))
			{
				$folder = JPATH_ROOT . '/' . $folder;
			}

			if (!Folder::exists($folder))
			{
				throw new RuntimeException();
			}

			$filename = $folder . '/' . $item->filename;

			if (!File::exists($filename))
			{
				throw new RuntimeException();
			}
		}
		catch (Exception $e)
		{
			throw new RuntimeException('Not found', 404, $e);
		}
	}


	/**
	 * Perform a file download
	 *
	 * @param   ItemTable      $item      The item record to download
	 * @param   CategoryTable  $category  The category the item belongs to
	 *
	 * @return  void
	 */
	public function doDownload(ItemTable $item, CategoryTable $category): void
	{
		$app = Factory::getApplication();

		// If it's a link we just have to redirect users
		if ($item->type == 'link')
		{
			if (@ob_get_length() !== false)
			{
				@ob_end_clean();
			}

			$this->logoutUser();

			$app->redirect($item->url);

			return;
		}

		try
		{
			$folder = $category->directory;

			if (!Folder::exists($folder))
			{
				$folder = JPATH_ROOT . '/' . $folder;
			}

			if (!Folder::exists($folder))
			{
				throw new RuntimeException();
			}

			$filename = $folder . '/' . $item->filename;

			if (!File::exists($filename))
			{
				throw new RuntimeException();
			}
		}
		catch (Exception $e)
		{
			$this->logoutUser();

			throw new RuntimeException('Not found', 404);
		}

		$basename  = @basename($filename);
		$filesize  = @filesize($filename);
		$mime_type = null;

		if (class_exists('finfo'))
		{
			$fInfo     = new finfo(FILEINFO_MIME_TYPE);
			$mime_type = $fInfo->file($filename);
		}

		$mime_type   = $mime_type ?: $this->get_mime_type($filename) ?: 'application/octet-stream';
		$header_file = $basename;

		// Clear cache
		/** @noinspection PhpStatementHasEmptyBodyInspection */
		while (@ob_end_clean())
		{
			// Make sure no junk will come before out content – to the extent we have a say on this...
		}

		// Fix IE bugs
		if ($app->client->engine == WebClient::TRIDENT)
		{
			$header_file = preg_replace('/\./', '%2e', $basename, substr_count($basename, '.') - 1);

			if (function_exists('ini_get') &&
				function_exists('ini_set') &&
				ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
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

		$retArray = $app->triggerEvent('onARSBeforeSendFile', [$object]) ?: [];

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

		@clearstatcache();

		// Disable caching
		header("Cache-Control: no-store, max-age=0, must-revalidate, no-transform", true);

		// Send MIME headers
		header('Content-Type: ' . $mime_type);
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="' . $header_file . '"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');

		error_reporting(0);
		set_time_limit(0);

		// Support resumable downloads
		$isResumable = false;
		$seek_start  = 0;
		$seek_end    = $filesize - 1;

		$range = $app->input->server->get('HTTP_RANGE', null, 'raw');

		if (!is_null($range) || (trim($range) === ''))
		{
			[$size_unit, $range_orig] = explode('=', $range, 2);

			if ($size_unit == 'bytes')
			{
				//multiple ranges could be specified at the same time, but for simplicity only serve the first range
				//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				/** @noinspection PhpUnusedLocalVariableInspection */
				[$range, $extra_ranges] = explode(',', $range_orig, 2);
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
			[$seek_start, $seek_end] = explode('-', $range, 2);

			// Set start and end based on range (if set), else set defaults. Also checks for invalid ranges.
			$seek_end   = (empty($seek_end)) ? ($filesize - 1) : min(abs(intval($seek_end)), ($filesize - 1));
			$seek_start =
				(empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

			$isResumable = true;
		}

		// Use 1M chunks for echoing the data to the browser
		$chunkSize = self::CHUNK_SIZE; //1M chunks
		$handle    = @fopen($filename, 'r');

		if ($handle === false)
		{
			// Notify of filesize, if this info is available
			if ($filesize > 0)
			{
				header('Content-Length: ' . (int) $filesize);
			}

			@readfile($filename);
		}
		else
		{
			$totalLength = 0;

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

			while (!feof($handle) && ($chunkSize > 0))
			{
				if ($isResumable && ($totalLength - $read < $chunkSize))
				{
					$chunkSize = $totalLength - $read;

					if ($chunkSize < 0)
					{
						continue;
					}
				}

				$buffer = fread($handle, $chunkSize);

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

		$ret = $app->triggerEvent('onARSAfterSendFile', [$object]) ?: [];

		foreach ($ret as $r)
		{
			if (!empty($r))
			{
				echo $r;
			}
		}

		$this->logoutUser();
	}

	/**
	 * Formats a string to a valid Download ID format. If the string is not looking like a Download ID it will return
	 * an empty string instead.
	 *
	 * @param   string|null  $dlid  The string to reformat.
	 *
	 * @return  string
	 */
	public function reformatDownloadID(?string $dlid): string
	{
		if (is_null($dlid))
		{
			return '';
		}

		$dlid = trim($dlid);

		// Is the Download ID empty or too short?
		if (empty($dlid) || (strlen($dlid) < 32))
		{
			return '';
		}

		// Do we have a userid:downloadid format?
		$user_id = null;

		if (strpos($dlid, ':') !== false)
		{
			$parts   = explode(':', $dlid, 2);
			$user_id = max(0, (int) $parts[0]) ?: null;
			$dlid    = rtrim($parts[1] ?? '');
		}

		if (empty($dlid))
		{
			return '';
		}

		// Trim the Download ID
		if (strlen($dlid) > 32)
		{
			$dlid = substr($dlid, 0, 32);
		}

		return (is_null($user_id) ? '' : $user_id . ':') . $dlid;
	}

	/**
	 * Gets the user associated with a specific Download ID
	 *
	 * @param   string|null  $downloadId  The Download ID to check
	 *
	 * @return  User  The user record of the corresponding user and the Download ID
	 *
	 * @throws  Exception  An exception is thrown if the Download ID is invalid or empty
	 */
	public function getUserFromDownloadID(?string $downloadId): User
	{
		// Reformat the Download ID
		$downloadId = $this->reformatDownloadID($downloadId);

		if (empty($downloadId))
		{
			throw new Exception('Invalid Download ID', 403);
		}

		// Do we have a userid:downloadid format?
		$user_id = null;

		if (strstr($downloadId, ':') !== false)
		{
			$parts      = explode(':', $downloadId, 2);
			$user_id    = (int) $parts[0];
			$downloadId = $parts[1];
		}

		$isPrimary = empty($user_id) ? 1 : 0;
		$db        = $this->getDatabase();
		$query     = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_dlidlabels'))
			->where($db->quoteName('dlid') . ' = :dlid')
			->where($db->quoteName('primary') . ' = :isPrimary')
            ->where($db->quoteName('published') . ' = 1')
			->bind(':isPrimary', $isPrimary, ParameterType::INTEGER)
			->bind(':dlid', $downloadId, ParameterType::STRING);

		if (!$isPrimary)
		{
			$query
				->where($db->quoteName('user_id') . ' = :user_id')
				->bind(':user_id', $user_id);
		}

		try
		{
			$matchingRecord = $db->setQuery($query)->loadObject() ?: null;

			$this->assertNotEmpty($matchingRecord, 'Unknown Download ID');
			$this->assertNotEmpty($matchingRecord->dlid ?? '', 'Invalid Download ID record');
			$this->assert(empty($user_id) || ($user_id == ($matchingRecord->user_id ?? 0)), 'Invalid User ID');
			$this->assert($downloadId == ($matchingRecord->dlid ?? ''), 'Invalid Download ID');

		}
		catch (Exception $e)
		{
			throw new Exception('Invalid Download ID', 403);
		}

		return Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($matchingRecord->user_id);
	}


	/**
	 * Log in a user if necessary
	 *
	 * @return  boolean  True if a user was logged in
	 */
	public function loginUser(): bool
	{
		/** @var SiteApplication $app */
		$app                     = Factory::getApplication();
		$this->haveLoggedInAUser = false;

		// No need to log in a user if the user is already logged in
		if (!$app->getIdentity()->guest)
		{
			return false;
		}

		$dlid = $this->reformatDownloadID($app->input->getString('dlid', ''));

		if (empty($dlid))
		{
			return false;
		}

		try
		{
			$user = $this->getUserFromDownloadID($dlid);
		}
		catch (Exception $exc)
		{
			$user = null;
		}

		if (empty($user) || empty($user->id) || $user->guest)
		{
			return false;
		}

		// Mark the user login so we can log him out later on
		$this->haveLoggedInAUser = true;

		// Get a fake login response
		$options            = ['remember' => false];
		$response           = new AuthenticationResponse();
		$response->status   = Authentication::STATUS_SUCCESS;
		$response->type     = 'downloadid';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		PluginHelper::importPlugin('user');
		$app->triggerEvent('onLoginUser', [(array) $response, $options]);

		// Set the user in the session, effectively logging in the user
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($response->username);

		$app->getSession()->set('user', $user);
		$app->loadIdentity($user);

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

		$app        = Factory::getApplication();
		$user       = $app->getIdentity();
		$options    = ['remember' => false];
		$parameters = [
			'username' => $user->username,
			'id'       => $user->id,
		];

		// Set clientid in the options array if it hasn't been set already and shared sessions are not enabled.
		if (!$app->get('shared_session', '0'))
		{
			$options['clientid'] = $app->getClientId();
		}

		$ret = $app->triggerEvent('onUserLogout', [$parameters, $options]);

		$haveLoggedOut = !in_array(false, $ret, true);

		$this->haveLoggedInAUser = !$haveLoggedOut;

		return $haveLoggedOut;
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
}