<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\RunPluginsTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use AllowDynamicProperties;
use Exception;
use finfo;
use Joomla\Application\Web\WebClient;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Http\HttpFactory;
use Joomla\Http\Response;
use Laminas\Diactoros\StreamFactory;
use RuntimeException;

#[AllowDynamicProperties]
class ItemModel extends BaseDatabaseModel
{
	use TableAssertionTrait;
	use RunPluginsTrait;

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

			try
			{
				$folderExists = @is_dir($folder);
			}
			catch (Exception $e)
			{
				$folderExists = false;
			}

			if (!$folderExists)
			{
				$folder = JPATH_ROOT . '/' . $folder;
			}

			try
			{
				$folderExists = @is_dir($folder);
			}
			catch (Exception $e)
			{
				$folderExists = false;
			}

			if (!$folderExists)
			{
				throw new RuntimeException();
			}

			$filename = $folder . '/' . $item->filename;

			try
			{
				$fileExists = @is_file($filename);
			}
			catch (Exception $e)
			{
				$fileExists = false;
			}

			if (!$fileExists)
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
	 * Handle the requested download
	 *
	 * @param   ItemTable      $item      The item record to download
	 * @param   CategoryTable  $category  The category the item belongs to
	 *
	 * @return  void
	 */
	public function doDownload(ItemTable $item, CategoryTable $category): void
	{
		// Clear cache
		while (@ob_get_length() !== false)
		{
			@ob_end_clean();
		}

		switch ($item->type)
		{
			case 'link':
				$this->downloadLinkItem($item, $category);

			default:
				$this->downloadFileItem($item, $category);
		}
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
		$query     = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
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

		$dlid = $this->reformatDownloadID($app->getInput()->getString('dlid', ''));

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
		$this->triggerPluginEvent('onLoginUser', [(array) $response, $options], null, $app);

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

		$ret = $this->triggerPluginEvent('onUserLogout', [$parameters, $options], null, $app);

		$haveLoggedOut = !in_array(false, $ret, true);

		$this->haveLoggedInAUser = !$haveLoggedOut;

		return $haveLoggedOut;
	}

	/**
	 * Handle download of an Item with type Link (we're given a URL).
	 *
	 * @param   ItemTable      $item
	 * @param   CategoryTable  $category
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   7.4.0
	 */
	private function downloadLinkItem(ItemTable $item, CategoryTable $category): void
	{
		$app      = Factory::getApplication();
		$cParams  = ComponentHelper::getParams('com_ars');
		$dlMethod = $cParams->get('url_dl', 'temp');

		// If we have to do a temporary or permanent redirect just do so without further ado.
		if (in_array($dlMethod, ['temp', 'permanent']))
		{
			$this->logoutUser();

			$httpCode = $dlMethod === 'temp' ? 303 : 301;

			$app->redirect($item->url, $httpCode);

			return;
		}

		// Get the basic information of the file we need to download
		$uri         = new Uri($item->url);
		$header_file = basename($uri->getPath());
		$cacheId     = 'dl_' . sha1($header_file . '#:#' . $app->get('secret'));

		// Get the applicable cache time for the download item.
		$cacheTime = (function () use ($cParams) {
			// If caching of guest downloads is disabled use the hardcoded default of one hour.
			if (!$cParams->get('allowcaching', 0))
			{
				return 60;
			}

			// Otherwise, round the “Maximum cache age (seconds)” up to the next whole minute.
			$seconds = max($cParams->get('caching_length', 30), 30);

			return (int) ceil($seconds / 30);
		})();

		// Get a callback cache controller
		/** @var CacheControllerFactoryInterface $cacheControllerFactory */
		$cacheControllerFactory = Factory::getContainer()->get('cache.controller.factory');
		/** @var CallbackController $cacheController */
		$cacheController = $cacheControllerFactory->createCacheController(
			'callback',
			[
				'lifetime'     => $cacheTime,
				'defaultgroup' => 'com_ars',
				'caching'      => true,
			]
		);

		// Get a cached object for the remote item from the cache.
		$callable      = function (string $url) {
			// We cannot serialise a PSR-7 Response object, hence the need for this conversion.
			$response = (new HttpFactory())->getHttp(['follow_location' => 1], ['curl', 'stream'])
				->get($url);

			return (object) [
				'body'       => (string) $response->getBody() ?? '',
				'statusCode' => $response->getStatusCode() ?? 200,
				'headers'    => $response->getHeaders() ?? [],
			];
		};
		/** @noinspection PhpParamsInspection */
		$responseData = $cacheController->get($callable, $item->url, $cacheId);

		// If the download had failed, we throw an exception right away
		if ($responseData->statusCode !== 200)
		{
			$cacheController->remove($cacheId, 'com_ars');

			throw new RuntimeException('The download item is temporarily unavailable.');
		}

		// This converts the arbitrary object back to a PSR-7 Response object
		$streamFactory = new StreamFactory;
		$httpResponse  = new Response(
			$streamFactory->createStream($responseData->body),
			$responseData->statusCode,
			$responseData->headers
		);

		// Now we can get rid of the memory hogging data. Bye-bye!
		unset($responseData);

		// Fix IE bugs
		if ($app->client->engine == WebClient::TRIDENT)
		{
			$header_file = preg_replace('/\./', '%2e', $header_file, substr_count($header_file, '.') - 1);

			if (function_exists('ini_get')
			    && function_exists('ini_set')
			    && ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}

		// Import ARS plugins
		PluginHelper::importPlugin('ars');

		// Call any plugins to post-process the download file parameters
		$mime_type = $this->getMimeTypeFromContentTypeHeader(
			array_reduce(
				$httpResponse->getHeader('Content-Type') ?? [],
				fn(?string $carry, ?string $header) => $carry ?? $header ?? null
			)
		) ?: 'application/octet-stream';
		$object    = [
			'rawentry'    => $item,
			'filename'    => $item->url,
			'basename'    => basename($uri->getPath()),
			'header_file' => $header_file,
			'mimetype'    => $mime_type,
			'filesize'    => array_reduce(
				$httpResponse->getHeader('Content-Length') ?? [],
				fn(?int $carry, ?string $header) => $carry ?? (
				empty($header) ? null : intval($header)
				)
			) ?: $item->filesize,
		];

		$retArray = $this->triggerPluginEvent('onARSBeforeSendFile', [$object], null, $app) ?: [];

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

		$headers = [
			'Cache-Control'             => 'no-store, max-age=0, must-revalidate, no-transform',
			'Content-Type'              => $mime_type,
			'Accept-Ranges'             => 'bytes',
			'Content-Disposition'       => "attachment; filename=\"$header_file\"",
			'Content-Transfer-Encoding' => 'binary',
		];

		// Should I add a Content-Digest header?
		if ($cParams->get('content_digest', 1) && $digest = $this->getContentDigestHeaderValue($item))
		{
			$headers['Content-Digest'] = $digest;
		}

		// Only guest downloads can be allowed to be cached
		if ($cParams->get('allowcaching', 0) && $app->getIdentity()->guest)
		{
			$cacheTime                = max(30, intval($cParams->get('caching_length', 864000)));
			$headers['Cache-Control'] = 'public, max-age=' . $cacheTime;
		}

		foreach ($headers as $header => $value)
		{
			header($header . ': ' . $value, true);
		}

		error_reporting(0);
		set_time_limit(0);

		// Support resumable downloads
		$isResumable = false;
		$seek_start  = 0;
		$seek_end    = $filesize - 1;

		$range = $app->getInput()->server->get('HTTP_RANGE', null, 'raw');

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

		if ($isResumable)
		{
			// Only send partial content header if downloading a piece of the file (IE workaround)
			if ($seek_start > 0 || $seek_end < ($filesize - 1))
			{
				header('HTTP/1.1 206 Partial Content');
			}

			// Necessary headers
			$totalLength = $seek_end - $seek_start + 1;

			header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $filesize);
			header('Content-Length: ' . $totalLength);
		}
		else
		{
			// Notify of filesize, if this info is available
			if ($filesize > 0)
			{
				header('Content-Length: ' . (int) $filesize);
			}
		}

		$bodyStream = $httpResponse->getBody();
		$bodyStream->rewind();

		if ($isResumable)
		{
			$bodyStream->seek($seek_start);

			echo $bodyStream->read($seek_end - $seek_start + 1);
		}
		else
		{
			echo $httpResponse->body;
		}

		@ob_flush();
		flush();

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

		$ret = $this->triggerPluginEvent('onARSAfterSendFile', [$object], null, $app) ?: [];

		foreach ($ret as $r)
		{
			if (!empty($r))
			{
				echo $r;
			}
		}

		$this->logoutUser();

		$app->close();
	}

	/**
	 * Handle download of an Item with type File (we're given a local file path).
	 *
	 * @param   ItemTable      $item
	 * @param   CategoryTable  $category
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   7.4.0
	 */
	private function downloadFileItem(ItemTable $item, CategoryTable $category): void
	{
		$app     = Factory::getApplication();
		$cParams = ComponentHelper::getParams('com_ars');

		try
		{
			$folder = $category->directory;

			try
			{
				$folderExists = @is_dir($folder);
			}
			catch (Exception $e)
			{
				$folderExists = false;
			}

			if (!$folderExists)
			{
				$folder = JPATH_ROOT . '/' . $folder;
			}

			try
			{
				$folderExists = @is_dir($folder);
			}
			catch (Exception $e)
			{
				$folderExists = false;
			}

			if (!$folderExists)
			{
				throw new RuntimeException();
			}

			$filename = $folder . '/' . $item->filename;

			try
			{
				$fileExists = @is_file($filename);
			}
			catch (Exception $e)
			{
				$fileExists = false;
			}

			if (!$fileExists)
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

		// Fix IE bugs
		if ($app->client->engine == WebClient::TRIDENT)
		{
			$header_file = preg_replace('/\./', '%2e', $basename, substr_count($basename, '.') - 1);

			if (function_exists('ini_get')
			    && function_exists('ini_set')
			    && ini_get('zlib.output_compression'))
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

		$retArray = $this->triggerPluginEvent('onARSBeforeSendFile', [$object], null, $app) ?: [];

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

		$headers = [
			'Cache-Control'             => 'no-store, max-age=0, must-revalidate, no-transform',
			'Content-Type'              => $mime_type,
			'Accept-Ranges'             => 'bytes',
			'Content-Disposition'       => "attachment; filename=\"$header_file\"",
			'Content-Transfer-Encoding' => 'binary',
		];

		// Should I add a Content-Digest header?
		if ($cParams->get('content_digest', 1) && $digest = $this->getContentDigestHeaderValue($item))
		{
			$headers['Content-Digest'] = $digest;
		}

		// Only guest downloads can be allowed to be cached
		if ($cParams->get('allowcaching', 0) && $app->getIdentity()->guest)
		{
			$cacheTime                = max(30, intval($cParams->get('caching_length', 864000)));
			$headers['Cache-Control'] = 'public, max-age=' . $cacheTime;
		}

		foreach ($headers as $header => $value)
		{
			header($header . ': ' . $value, true);
		}

		error_reporting(0);
		set_time_limit(0);

		// Support resumable downloads
		$isResumable = false;
		$seek_start  = 0;
		$seek_end    = $filesize - 1;

		$range = $app->getInput()->server->get('HTTP_RANGE', null, 'raw');

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

					// Zero, not just negative: when the requested range ends exactly on a chunk
					// boundary there is nothing left to send, and fread() with a zero length throws
					// a ValueError on PHP 8. Letting the loop condition end the read is what we want
					// in both cases.
					if ($chunkSize <= 0)
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

		$ret = $this->triggerPluginEvent('onARSAfterSendFile', [$object], null, $app) ?: [];

		foreach ($ret as $r)
		{
			if (!empty($r))
			{
				echo $r;
			}
		}

		$this->logoutUser();

		$app->close();
	}

	/**
	 * Get the MIME type of a local file.
	 *
	 * @param   string  $filename  Absolute path to the file.
	 *
	 * @return  string
	 * @since   7.0.0
	 */
	private function get_mime_type(string $filename): string
	{
		$type = function_exists('mime_content_type') ? @mime_content_type($filename) : false;

		if ($type === false)
		{
			$type = 'application/octet-stream';
		}

		return $type;
	}

	/**
	 * Returns the most secure item digest available in Content-Digest representation format.
	 *
	 * @param   ItemTable  $item  The item to calculate the Content-Digest header value for.
	 *
	 * @return  string|null  The header value, NULL if not available
	 * @since   7.4.0
	 * @link    https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Repr-Digest
	 */
	private function getContentDigestHeaderValue(ItemTable $item): ?string
	{
		if (!empty($item->sha512))
		{
			return 'sha-512=:' . base64_encode(hex2bin($item->sha512)) . ':';
		}

		if (!empty($item->sha256))
		{
			return 'sha-256=:' . base64_encode(hex2bin($item->sha256)) . ':';
		}

		if (!empty($item->sha1))
		{
			return 'sha=:' . base64_encode(hex2bin($item->sha1)) . ':';
		}

		if (!empty($item->md5))
		{
			return 'md5=:' . base64_encode(hex2bin($item->md5)) . ':';
		}

		return null;
	}

	/**
	 * Get the MIME-type of an item given a Content-Type HTTP header.
	 *
	 * @param   string|null  $header
	 *
	 * @return  string|null
	 * @since   7.4.0
	 */
	private function getMimeTypeFromContentTypeHeader(?string $header): ?string
	{
		$header = trim($header ?? '');

		if (empty($header))
		{
			return null;
		}

		if (str_contains($header, ':'))
		{
			[, $header] = explode(':', $header, 2);
		}

		if (str_contains($header, ';'))
		{
			[$header,] = explode(';', $header);
		}

		return trim($header);
	}
}
