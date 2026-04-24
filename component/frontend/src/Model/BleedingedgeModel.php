<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\RunPluginsTrait;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Filesystem\File;
use Throwable;

#[\AllowDynamicProperties]
final class BleedingedgeModel extends BaseDatabaseModel
{
	use RunPluginsTrait;

	/**
	 * Scan a Bleeding Edge category.
	 *
	 * Limitations:
	 * - If a BE release is unpublished without its directory removed, it will fail trying to re-add it.
	 * - If you modify the uploaded files in an already published BE release the new files will NOT be added.
	 * - If you delete the directory of a BE release it gets unpublished, not deleted, to maintain association with
	 *   `#__ars_log` table entries.
	 *
	 * @param   CategoryTable  $category  The category to scan
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   1.0.0
	 */
	public function scanCategory(CategoryTable $category): void
	{
		// Get the full path to the BE directory
		$path = $this->getDirectoryPath($category);

		if (empty($path))
		{
			return;
		}

		// Apply age and count limits
		$this->removeReleasesByAge($category);
		$this->removeReleasesByCount($category);

		// Get the ground truth from the filesystem and database
		$directoryContents   = $this->scanDirectory($path);
		$publishedReleases   = $this->ensureModifiedPopulated($this->getPublishedReleases($category));
		$unpublishedReleases = $this->ensureModifiedPopulated(
			$this->getUnpublishedReleases($category, array_keys($directoryContents))
		);

		// Find the versions to add to and unpublish from the database
		$versionsInFS    = array_keys($directoryContents);
		$versionsInDB    = array_merge(array_keys($publishedReleases), array_keys($unpublishedReleases));
		$newVersions     = array_diff($versionsInFS, $versionsInDB);
		$removedVersions = array_diff($versionsInDB, $versionsInFS);

		/**
		 * Find out which versions need to be updated.
		 *
		 * This is trickier, as it may come from TWO sources:
		 * 1. Version published in the DB, but its modified time is older than the filesystem modified time.
		 * 2. This version is unpublished in the DB, but its directory exists.
		 */
		$updatedVersions = array_merge(
			array_filter(
				array_intersect($versionsInFS, $versionsInDB),
				fn($key) => $directoryContents[$key]['modified'] > ($unpublishedReleases[$key]['modified'] ?? $publishedReleases[$key]['modified'])
			),
			array_intersect($versionsInFS, array_keys($unpublishedReleases))
		);

		// If there's no work to do we can buzz off.
		if (empty($newVersions) && empty($removedVersions) && empty($updatedVersions))
		{
			return;
		}

		// Let's create a temporary array which maps version strings to known release IDs
		$allVersionIDs = array_map(
			fn($x) => $x['id'],
			array_merge($publishedReleases, $unpublishedReleases)
		);

		// Wrap everything in a transaction
		$db = $this->getDatabase();

		try
		{
			$db->transactionStart();
		}
		catch (Throwable)
		{
			// No-op
		}

		// Unpublish releases and their items
		$this->unpublishReleasesAndItems(
			array_filter(
				$allVersionIDs,
				fn($version) => in_array($version, $unpublishedReleases),
				ARRAY_FILTER_USE_KEY
			)
		);

		// Add new releases
		foreach ($newVersions as $version)
		{
			$infoArray = $directoryContents[$version];

			$this->addNewRelease($category, $version, $infoArray);
		}

		// Update releases
		foreach ($updatedVersions as $version)
		{
			$infoArray = $directoryContents[$version];

			$this->updateRelease($allVersionIDs[$version], $infoArray);
		}

		try
		{
			$db->transactionCommit();
		}
		catch (Throwable)
		{
			// No-op
		}
	}

	/**
	 * Removes stale Bleeding Edge release by age.
	 *
	 * IMPORTANT! The age of a BE release is calculated against the `created` date in the database.
	 *
	 * @param   CategoryTable  $category
	 * @param   int            $ageLimit
	 *
	 * @return void
	 * @throws \DateInvalidOperationException
	 */
	private function removeReleasesByAge(CategoryTable $category): void
	{
		$cParams  = ComponentHelper::getParams($this->option);
		$ageLimit = $cParams->get('bleedingedge_age', 0);

		if ($ageLimit <= 0)
		{
			return;
		}

		/** @var DatabaseDriver $db */
		$db               = $this->getDatabase();
		$tz = new \DateTimeZone('UTC');
		$targetDatePHP    = (new \DateTime('now', $tz))->sub(new \DateInterval(sprintf('P%dD', $ageLimit)));
		$targetDateJoomla = new Date($targetDatePHP->format(DATE_ATOM), $tz);
		$dateString       = $targetDateJoomla->toSql(false, $db);
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('version'),
			]
		)
			->from($db->quoteName('#__ars_releases'))
			->where(
				[
					$db->quoteName('published') . ' = 1',
					$db->quoteName('created') . ' > :dateString',
				]
			)
			->bind(':dateString', $dateString, ParameterType::STRING);

		$results = $db->setQuery($query)->loadAssocList('id', 'version');

		if (empty($results))
		{
			return;
		}

		// First, unpublish the releases and their contained items.
		$db->transactionStart();
		$this->unpublishReleasesAndItems(array_keys($results));
		$db->transactionCommit();

		// Then, delete the actual filesystem directories
		$basePath = $this->getDirectoryPath($category);

		foreach ($results as $version)
		{
			$this->recursiveRmdir($basePath . DIRECTORY_SEPARATOR . $version);
		}
	}

	/**
	 * Remove BE releases exceeding the count limit along with their associated filesystem directories.
	 *
	 * This method retrieves published releases, unpublishes them, and removes their corresponding
	 * directories from the filesystem if the number of published releases exceeds the specified limit.
	 *
	 * @param   CategoryTable  $category  The category table instance used to determine release directories.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function removeReleasesByCount(CategoryTable $category): void
	{
		$cParams    = ComponentHelper::getParams($this->option);
		$countLimit = $cParams->get('bleedingedge_count', 0);

		if ($countLimit < 1)
		{
			return;
		}

		/** @var DatabaseDriver $db */
		$db = $this->getDatabase();
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));

		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('version'),
			]
		)
			->from($db->quoteName('#__ars_releases'))
			->where(
				[
					$db->quoteName('published') . ' = 1',
				]
			);

		$results = $db->setQuery($query, $countLimit)->loadAssocList('id', 'version');

		if (empty($results))
		{
			return;
		}

		// First, unpublish the releases and their contained items.
		$db->transactionStart();
		$this->unpublishReleasesAndItems(array_keys($results));
		$db->transactionCommit();

		// Then, delete the actual filesystem directories
		$basePath = $this->getDirectoryPath($category);

		foreach ($results as $version)
		{
			$this->recursiveRmdir($basePath . DIRECTORY_SEPARATOR . $version);
		}
	}

	/**
	 * Recursively deletes a directory and its contents.
	 *
	 * @param   string  $path  The absolute path to the directory to delete
	 *
	 * @return  bool  True on success, false on failure
	 * @since   7.5.0
	 */
	private function recursiveRmdir(string $path): bool
	{
		if (!@is_dir($path))
		{
			return false;
		}

		try
		{
			$di = new \DirectoryIterator($path);

			foreach ($di as $item)
			{
				if ($item->isDot())
				{
					continue;
				}

				if ($item->isDir())
				{
					if (!$this->recursiveRmdir($item->getPathname()))
					{
						return false;
					}

					continue;
				}

				if (!@unlink($item->getPathname()))
				{
					try
					{
						File::delete($item->getPathname());
					}
					catch (Throwable $e)
					{
						return false;
					}
				}
			}

			return @rmdir($path);
		}
		catch (Throwable $e)
		{
			return false;
		}
	}

	/**
	 * Gets the full filesystem path of the Bleeding Edge directory.
	 *
	 * @param   CategoryTable  $category  The Category table
	 *
	 * @return  string|null  Absolute filesystem path; NULL if it's not set or does not exist
	 * @since   7.5.0
	 */
	private function getDirectoryPath(CategoryTable $category): ?string
	{
		$nominalDir = $category->directory;

		if (!is_string($nominalDir) || empty(trim($nominalDir)))
		{
			return null;
		}

		$nominalDir = trim($nominalDir);

		$dirs = [
			JPATH_ROOT . DIRECTORY_SEPARATOR . $nominalDir,
			$nominalDir,
		];

		foreach ($dirs as $dir)
		{
			if (@is_dir($dir))
			{
				return $dir;
			}
		}

		return null;
	}

	/**
	 * Scan a Bleeding Edge directory to get the releases and their contained files.
	 *
	 * @param   string  $path  The absolute filesystem path to scan
	 *
	 * @return  array  The releases, keyed by version (path name).
	 * @since   7.5.0
	 */
	private function scanDirectory(string $path): array
	{
		$ret = [];
		$di  = new \DirectoryIterator($path);

		/** @var \DirectoryIterator $dir */
		foreach ($di as $dir)
		{
			if ($dir->isDot() || !$dir->isDir())
			{
				continue;
			}

			$ret[$dir->getFilename()] = [
				'modified' => $dir->getMTime(),
				'items'    => $this->scanSubdirectory($dir->getPathname()),
			];
		}

		return $ret;
	}

	/**
	 * Scan a Bleeding Edge subdirectory to get the files in that BE release.
	 *
	 * @param   string  $path  The absolute filesystem path to scan
	 *
	 * @return  array  The files, keyed by filename.
	 * @since   7.5.0
	 */
	private function scanSubdirectory(string $path): array
	{
		$ret = [];
		$di  = new \DirectoryIterator($path);

		/** @var \DirectoryIterator $file */
		foreach ($di as $file)
		{
			if ($file->isDot() || !$file->isFile())
			{
				continue;
			}

			$ret[$file->getFilename()] = [
				'path'  => $file->getPathname(),
				'size'  => $file->getSize(),
				'mtime' => $file->getMTime(),
			];
		}

		return $ret;
	}

	/**
	 * Get the published BE releases in the database for this category
	 *
	 * @param   CategoryTable  $categoryTable  The BE category to scan
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	private function getPublishedReleases(CategoryTable $categoryTable): array
	{
		$catId = $categoryTable->id;
		$db    = $this->getDatabase();
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('version'),
				$db->quoteName('modified'),
				$db->quoteName('created'),
			]
		)
			->from($db->quoteName('#__ars_releases'))
			->where(
				[
					$db->quoteName('category_id') . ' = :catId',
					$db->quoteName('published') . ' = 1',
				]
			)
			->bind(':catId', $catId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList('version');
	}

	/**
	 * Get the unpublished BE releases in the database which have corresponding entries in the filesystem.
	 *
	 * The idea is that these will need to be republished.
	 *
	 * @param   CategoryTable  $categoryTable
	 * @param   array          $versionsInFilesystem
	 *
	 * @return  array
	 * @since   7.5.0
	 */
	private function getUnpublishedReleases(CategoryTable $categoryTable, array $versionsInFilesystem): array
	{
		if (empty($versionsInFilesystem))
		{
			return [];
		}

		$catId = $categoryTable->id;
		$db    = $this->getDatabase();
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('version'),
				$db->quoteName('modified'),
				$db->quoteName('created'),
			]
		)
			->from($db->quoteName('#__ars_releases'))
			->where(
				[
					$db->quoteName('category_id') . ' = :catId',
					$db->quoteName('published') . ' = 0',
				]
			)
			->whereIn($db->quoteName('version'), $versionsInFilesystem, ParameterType::STRING)
			->bind(':catId', $catId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList('version');
	}

	/**
	 * Unpublishes releases and their included items.
	 *
	 * @param   array  $releaseIDs
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function unpublishReleasesAndItems(array $releaseIDs): void
	{
		if (empty($releaseIDs))
		{
			return;
		}

		$db = $this->getDatabase();

		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->update($db->quoteName('#__ars_releases'))
			->set($db->quoteName('published') . ' = 0')
			->whereIn($db->quoteName('id'), $releaseIDs, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Throwable)
		{
			return;
		}

		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->update($db->quoteName('#__ars_items'))
			->set($db->quoteName('published') . ' = 0')
			->whereIn($db->quoteName('release_id'), $releaseIDs, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Throwable)
		{
			return;
		}
	}

	/**
	 * Ensures that the 'modified' field is populated in the given entries and contains a UNIX timestamp.
	 *
	 * This method updates the 'modified' value for each entry in the provided list, using the following fallback
	 * logic:
	 * - 'modified' takes precedence if it exists and is valid.
	 * - If 'modified' is missing or invalid, it falls back to the 'created' field.
	 * - If both 'modified' and 'created' are invalid, a default timestamp is used.
	 * - The 'created' field is removed from the resulting entries.
	 *
	 * @param   array  $entries  An array of associative arrays containing at least 'modified' and 'created' fields.
	 *
	 * @return  array  The modified array of entries, with the 'modified' field ensured to be populated and 'created'
	 *                 removed.
	 * @since   7.5.0
	 */
	private function ensureModifiedPopulated(array $entries): array
	{
		if (empty($entries))
		{
			return $entries;
		}

		$db = $this->getDatabase();

		return array_map(
			function ($entry) use (&$db) {
				$modified = $entry['modified'];

				if (empty($modified) || $modified == $db->getNullDate() || $modified == '0000-00-00 00:00:00')
				{
					$modified = $entry['created'] ?? '0000-00-00 00:00:00';
				}

				if (empty($modified) || $modified == $db->getNullDate() || $modified == '0000-00-00 00:00:00')
				{
					$modified = '2001-01-01 00:00:00';
				}

				try
				{
					$entry['modified'] = (new \DateTime($modified, new \DateTimeZone('UTC')))->getTimestamp();
				}
				catch (\DateError)
				{
					$entry['modified'] = 0;
				}

				if (array_key_exists('created', $entry))
				{
					unset($entry['created']);
				}

				return $entry;
			},
			$entries
		);
	}

	/**
	 * Adds a new software release to the system, including its associated items.
	 *
	 * @param   CategoryTable  $category   The category under which the new release will be created.
	 * @param   string         $version    The version label for the new release.
	 * @param   array          $infoArray  An associative array containing release metadata, including the items to be
	 *                                     added. Expected structure:
	 *                                     - items: An array of file information arrays, where each array includes at a
	 *                                     minimum:
	 *                                     - path: The file path of the item to be associated with the release.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	private function addNewRelease(CategoryTable $category, string $version, array $infoArray): void
	{
		/** @var DatabaseDriver $db */
		$db = $this->getDatabase();

		$referenceDate = Date::getInstance($infoArray['modified'], 'UTC');

		try
		{
			/** @var ReleaseTable $releaseTable */
			$releaseTable = $this->getMVCFactory()->createTable('Release');
			$success      = $releaseTable->save(
				[
					'category_id'       => $category->id,
					'version'           => $version,
					'maturity'          => 'alpha',
					'notes'             => '',
					'hits'              => 0,
					'created'           => $referenceDate->toSql($db),
					'created_by'        => 0,
					'modified'          => $referenceDate->toSql($db),
					'modified_by'       => 0,
					'checked_out'       => 0,
					'checked_out_time'  => null,
					'ordering'          => 0,
					'access'            => $category->access,
					'show_unauth_links' => 0,
					'published'         => 1,
					'language'          => $category->language ?: '*',
				]
			);

			if (!$success)
			{
				return;
			}
		}
		catch (Throwable)
		{
			// We failed. We cannot create items.
			return;
		}

		// Create the items.
		foreach ($infoArray['items'] as $fileInfo)
		{
			try
			{
				$itemDate = Date::getInstance($fileInfo['mtime'], 'UTC');

				/** @var ItemTable $itemTable */
				$itemTable = $this->getMVCFactory()->createTable('Item');
				$itemTable->save(
					[
						'id'               => 0,
						'release_id'       => $releaseTable->id,
						'description'      => '',
						'type'             => 'file',
						'filename'         => $fileInfo['path'],
						'url'              => '',
						'hits'             => '0',
						'published'        => '1',
						'created'          => $itemDate->toSql(),
						'created_by'       => 0,
						'modified'         => $itemDate->toSql(),
						'modified_by'      => 0,
						'checked_out'      => 0,
						'checked_out_time' => null,
						'access'           => $releaseTable->access,
					]
				);
			}
			catch (Throwable)
			{
				// No-op
			}
		}
	}

	private function updateRelease($releaseId, $infoArray)
	{
		/** @var ReleaseTable $releaseTable */
		$releaseTable = $this->getMVCFactory()->createTable('Release');

		// Make sure we can load the release
		if (!$releaseTable->load($releaseId))
		{
			return;
		}

		// Update the release itself
		/** @var DatabaseDriver $db */
		$db = $this->getDatabase();

		// Do not update the created and modified date/time stamps.
		$releaseTable->setUpdateCreated(false);
		$releaseTable->setUpdateModified(false);

		try
		{
			$success = $releaseTable->save(
				[
					'modified'         => Date::getInstance($infoArray['modified'], 'UTC')->toSql($db),
					'modified_by'      => 0,
					'checked_out'      => 0,
					'checked_out_time' => null,
					'ordering'         => 0,
					'published'        => 1,
				]
			);
		}
		catch (Throwable)
		{
			return;
		}

		if (!$success)
		{
			return;
		}

		// Get all the items in the release
		/** @var QueryInterface $query */
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true));
		$query->select(
			[
				$db->quoteName('id'),
				$db->quoteName('filename'),
				$db->quoteName('created'),
				$db->quoteName('modified'),
			]
		)
			->from($db->quoteName('#__ars_items'))
			->where(
				[
					$db->quoteName('release_id') . ' = :release_di',
				]
			)
			->bind(':release_di', $releaseId, ParameterType::INTEGER);

		$fileInfoInDB = $this->ensureModifiedPopulated($db->setQuery($query)->loadAssocList('filename'));

		// Find added, removed, and modified items
		$knownFilesInfo = $infoArray['items'];
		$filenamesInDB = array_map(basename(...), array_keys($fileInfoInDB));
		$filenamesInFS = array_map(basename(...), array_keys($knownFilesInfo));

		$addedFilenames       = array_diff($filenamesInDB, $filenamesInFS);
		$modifiedFilenames    = array_intersect($filenamesInDB, $filenamesInFS);
		$unpublishedFilenames = array_diff($filenamesInFS, $filenamesInDB);

		foreach ($unpublishedFilenames as $fname)
		{
			/** @var ItemTable $itemTable */
			$itemTable = $this->getMVCFactory()->createTable('Release');
			$loaded = $itemTable->load([
				'filename' => $fname,
				'release_id' => $releaseId
			]);

			if (!$loaded)
			{
				continue;
			}

			$itemTable->save([
				'published' => 0
			]);
		}

		foreach ($addedFilenames as $fname)
		{
			$fileInfo = $infoArray['items'][$fname];
			$itemDate = Date::getInstance($fileInfo['mtime'], 'UTC');

			/** @var ItemTable $itemTable */
			$itemTable = $this->getMVCFactory()->createTable('Item');
			$itemTable->save(
				[
					'id'               => 0,
					'release_id'       => $releaseTable->id,
					'description'      => '',
					'type'             => 'file',
					'filename'         => $fileInfo['path'],
					'url'              => '',
					'hits'             => '0',
					'published'        => '1',
					'created'          => $itemDate->toSql(),
					'created_by'       => 0,
					'modified'         => $itemDate->toSql(),
					'modified_by'      => 0,
					'checked_out'      => 0,
					'checked_out_time' => null,
					'access'           => $releaseTable->access,
				]
			);
		}

		foreach ($modifiedFilenames as $fname)
		{
			/** @var ItemTable $itemTable */
			$itemTable = $this->getMVCFactory()->createTable('Item');
			$loaded = $itemTable->load([
				'filename' => $fname,
				'release_id' => $releaseId
			]);

			if (!$loaded)
			{
				continue;
			}

			$fileInfo = $infoArray['items'][$fname];
			$itemDate = Date::getInstance($fileInfo['mtime'], 'UTC');

			$itemTable->save(
				[
					'published'        => '1',
					'modified'         => $itemDate->toSql(),
					'modified_by'      => 0,
					'checked_out'      => 0,
					'checked_out_time' => null,
					'access'           => $releaseTable->access,
					'md5' => null,
					'sha1' => null,
					'sha256' => null,
					'sha384' => null,
					'sha512' => null,
					'filesize' => null,
				]
			);
		}
	}
}
