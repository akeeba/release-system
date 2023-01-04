<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Akeeba\Component\ARS\Administrator\Table\ItemTable;
use Akeeba\Component\ARS\Administrator\Table\ReleaseTable;
use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

#[\AllowDynamicProperties]
class BleedingedgeModel extends BaseDatabaseModel
{
	/**
	 * The numeric ID of the BleedingEdge category we're operating on
	 *
	 * @var  int
	 */
	private $category_id;

	/**
	 * The BleedingEdge category we're operating on
	 *
	 * @var  CategoryTable
	 */
	private $category;

	/**
	 * The absolute path to the category's folder
	 *
	 * @var  string
	 */
	private $folder = null;

	/**
	 * Scan a bleeding edge category
	 *
	 * @param   CategoryTable  $category  The category to scan
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function scanCategory(CategoryTable $category): void
	{
		$this->setCategory($category->id);

		// Can't proceed without a category
		if (empty($this->category))
		{
			return;
		}

		// Can't proceed without a folder
		if (empty($this->folder))
		{
			return;
		}

		// Can't proceed if it's not a BleedingEdge category
		if ($this->category->type != 'bleedingedge')
		{
			return;
		}

		// Get the component parameters
		$cParams = ComponentHelper::getParams($this->option);

		// We will now prune releases based on the existence of their files, their age and their count.
		$known_folders = [];

		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('category_id') . ' = :catid')
			->bind(':catid', $category->id);

		/** @var ReleaseTable $release */
		$release  = $this->getMVCFactory()->createTable('Release');
		$releases = array_map(function ($data) use ($release) {
			$ret = clone $release;
			$ret->reset();
			$ret->bind($data);

			return $ret;
		}, $db->setQuery($query)->loadAssocList('id') ?: []);

		// Releases pointing to non-existent folders will be deleted
		$toDelete = array_filter($releases, function (ReleaseTable $release) use (&$known_folders) {
			// Already unpublished releases will be automatically deleted
			if (!$release->published)
			{
				return true;
			}

			// Releases with an invalid folder name will be automatically deleted
			$folderName = $this->getReleaseFolder($this->folder, $release->version, $release->alias, $release->maturity);

			if (is_null($folderName))
			{
				return true;
			}

			// Releases whose folder no longer exists will be automatically deleted
			$folder = $this->folder . '/' . $folderName;
			$exists = Folder::exists($folder);

			if (!$exists)
			{
				return true;
			}

			// The folder exists. Add it to the known folders array and check the files of this BE release.
			$known_folders[] = $folderName;

			$this->checkFiles($release);

			return false;
		});

		// Keep the releases which are not already marked for deletion. Avoids double entries in $toDelete.
		if (count($toDelete))
		{
			$releases = array_diff($releases, $toDelete);
		}

		// Apply maximum age limits
		$ageLimit = $cParams->get('bleedingedge_age', 0);

		if ($ageLimit > 0)
		{
			// Releases older than this timestamp are to be deleted
			$targetTimestamp = time() - (86400 * $ageLimit);

			// Find which BleedingEdge releases I need to delete by age
			$toDelete = array_merge(
				$toDelete,
				array_filter($releases, function (ReleaseTable $release) use ($targetTimestamp) {
					try
					{
						return (clone Factory::getDate($release->created))->getTimestamp() <= $targetTimestamp;
					}
					catch (Exception $e)
					{
						// The release creation timestamp is invalid. Delete the sucker anyway.
						return true;
					}
				})
			);

			// Keep the releases which are not already marked for deletion
			if (count($toDelete))
			{
				$releases = array_diff($releases, $toDelete);
			}
		}

		// Apply count limits
		$countLimit = $cParams->get('bleedingedge_count', 0);

		if (($countLimit > 0) && (count($releases) > $countLimit))
		{
			// Add the excess releases in the collection of releases to remove
			$toDelete = array_merge(
				$toDelete,
				array_slice($releases, $countLimit)
			);

			// Conversely, only keep as many releases as I was told to keep
			$releases = array_slice($releases, 0, $countLimit);
		}

		// Remove any leftover releases
		foreach ($toDelete as $release)
		{
			$this->recursiveDeleteRelease($release);
		}

		// Sort releases in ascending order
		usort($releases, function (ReleaseTable $a, ReleaseTable $b) {
			return $a->getId() <=> $b->getId();
		});

		// Get the latest release, used to calculate the CHANGELOG
		$first_release   = end($releases);
		$first_changelog = [];

		/** @var ReleaseTable $first_release */
		if (is_object($first_release))
		{
			$changelog = $this->folder . '/' . $first_release->alias . '/CHANGELOG';

			if (File::exists($changelog))
			{
				$changeLogData   = @file_get_contents($changelog);
				$first_changelog = explode("\n", str_replace("\r\n", "\n", $changeLogData));
			}
		}

		// Get a list of all folders
		$allFolders = Folder::folders($this->folder);

		if (!empty($allFolders))
		{
			foreach ($allFolders as $folder)
			{
				if (!in_array($folder, $known_folders))
				{
					// Create a new entry
					$notes         = '';
					$changelog     = $this->folder . '/' . $folder . '/' . 'CHANGELOG';
					$changeLogData = '';

					if (File::exists($changelog))
					{
						$changeLogData = @file_get_contents($changelog);
						$changeLogData = ($changeLogData === false) ? '' : $changeLogData;
						$notes         = $this->coloriseChangelog($changeLogData, $first_changelog);
					}

					$jNow = clone Factory::getDate();

					$alias = ApplicationHelper::stringURLSafe($folder);

					$data = [
						'id'          => 0,
						'category_id' => $this->category_id,
						'version'     => $folder,
						'alias'       => $alias,
						'maturity'    => 'alpha',
						'description' => '',
						'notes'       => $notes,
						'access'      => $this->category->access,
						'published'   => 1,
						'created'     => $jNow->toSql(),
					];

					// Before saving the release, call the onNewARSBleedingEdgeRelease()
					// event of ars plugins so that they have the chance to modify
					// this information.

					// -- Load plugins
					PluginHelper::importPlugin('ars');

					// -- Setup information data
					$infoData = [
						'folder'          => $folder,
						'category_id'     => $this->category_id,
						'category'        => $this->category,
						'has_changelog'   => !empty($changeLogData),
						'changelog_file'  => $changelog,
						'changelog'       => $changeLogData,
						'first_changelog' => $first_changelog,
					];

					// -- Trigger the plugin event
					$app       = Factory::getApplication();
					$jResponse = $app->triggerEvent('onNewARSBleedingEdgeRelease', [
						$infoData,
						$data,
					]);

					// -- Merge response
					if (is_array($jResponse))
					{
						foreach ($jResponse as $response)
						{
							if (is_array($response))
							{
								$data = array_merge($data, $response);
							}
						}
					}

					// -- Create the BE release
					try
					{
						/** @var ReleaseTable $table */
						$table = $this->getMVCFactory()->createTable('Release');
						$table->save($data);
						$this->checkFiles($table);
					}
					catch (Exception $e)
					{
					}
				}
			}
		}
	}

	public function checkFiles(ReleaseTable $release): void
	{
		if (!$release->id)
		{
			return;
		}

		// Make sure we are given a release which exists
		if (empty($release->category_id))
		{
			return;
		}

		// Set the category from the release if the model's category doesn't match
		if (($this->category_id != $release->category_id) || empty($this->folder))
		{
			$this->setCategory($release->category_id);
		}

		// Make sure the category was indeed set
		if (empty($this->category) || empty($this->category_id) || empty($this->folder))
		{
			return;
		}

		// Can't proceed if it's not a bleedingedge category
		if ($this->category->type != 'bleedingedge')
		{
			return;
		}

		// Safe fallback
		$folderName = $this->getReleaseFolder($this->folder, $release->version, $release->alias, $release->maturity);

		if (is_null($folderName))
		{
			// Normally this shouldn't happen!
			return;
		}

		$folder          = $this->folder . '/' . $folderName;

		// Do we have a changelog?
		if (empty($release->notes))
		{
			$changelog      = $folder . '/CHANGELOG';
			$hasChangelog   = false;
			$this_changelog = '';

			if (File::exists($changelog))
			{
				$hasChangelog   = true;
				$this_changelog = @file_get_contents($changelog);
			}

			if ($hasChangelog)
			{
				$first_changelog = [];
				$notes           = $this->coloriseChangelog($this_changelog, $first_changelog);
				$release->notes  = $notes;

				$release->store();
			}
		}

		// Get the items
		$db         = $this->getDatabase();
		$release_id = $release->id;
		$query      = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_items'))
			->where($db->quoteName('release_id') . ' = :relid')
			->bind(':relid', $release_id);

		/** @var ItemTable $item */
		$item = $this->getMVCFactory()->createTable('Item');

		$items = array_map(function ($data) use ($item) {
			$ret = clone $item;
			$ret->reset();
			$ret->bind($data);
			$ret->setUpdateModified(false);
			$ret->setUpdateCreated(false);

			return $ret;
		}, $db->setQuery($query)->loadAssocList() ?: []);

		$known_items = [];

		$files = Folder::files($folder);

		foreach ($items as $item)
		{
			$known_items[] = basename($item->filename);

			if ($item->published && !in_array(basename($item->filename), $files))
			{
				$item->save([
					'published' => 0,
				]);
			}

			if (!$item->published && in_array(basename($item->filename), $files))
			{
				$item->save([
					'published' => 1,
				]);
			}
		}

		foreach ($files as $file)
		{
			if (basename($file) == 'CHANGELOG')
			{
				continue;
			}

			if (in_array($file, $known_items))
			{
				continue;
			}

			$jNow = clone Factory::getDate();
			$data = [
				'id'          => 0,
				'release_id'  => $release->id,
				'description' => '',
				'type'        => 'file',
				'filename'    => $folderName . '/' . $file,
				'url'         => '',
				'hits'        => '0',
				'published'   => '1',
				'created'     => $jNow->toSql(),
				'access'      => $release->access,
			];

			// Before saving the item, call the onNewARSBleedingEdgeItem()
			// event of ars plugins so that they have the chance to modify
			// this information.
			// -- Load plugins
			PluginHelper::importPlugin('ars');
			// -- Setup information data
			$infoData = [
				'folder'     => $folder,
				'file'       => $file,
				'release_id' => $release->id,
				'release'    => $release,
			];
			// -- Trigger the plugin event
			$app       = Factory::getApplication();
			$jResponse = $app->triggerEvent('onNewARSBleedingEdgeItem', [
				$infoData,
				$data,
			]);
			// -- Merge response
			if (is_array($jResponse))
			{
				foreach ($jResponse as $response)
				{
					if (is_array($response))
					{
						$data = array_merge($data, $response);
					}
				}
			}

			if ($data['ignore'] ?? false)
			{
				continue;
			}

			/** @var ItemTable $item */
			$table = $this->getMVCFactory()->createTable('Item');
			$table->save($data);
		}

		if (isset($table) && is_object($table) && method_exists($table, 'reorder'))
		{
			$db = $table->getDbo();

			$table->reorder($db->qn('release_id') . ' = ' . $db->q($release->id));
		}
	}

	/**
	 * Sets the category we are operating on
	 *int
	 *
	 * @param   int  $catId  A category table or a numeric category ID
	 *
	 * @return void
	 */
	protected function setCategory(int $catId): void
	{
		// Initialise
		$this->folder      = null;
		$this->category_id = (int) $catId;
		$this->category    = $this->getMVCFactory()->createTable('Category');
		$this->category->load($this->category_id);

		// Store folder
		$folder = $this->category->directory;

		// If it is stored locally, make sure the folder exists
		if (!Folder::exists($folder))
		{
			$folder = JPATH_ROOT . '/' . $folder;

			if (!Folder::exists($folder))
			{
				return;
			}
		}

		$this->folder = $folder;
	}

	private function coloriseChangelog(&$this_changelog, array $first_changelog = []): string
	{
		$this_changelog = explode("\n", str_replace("\r\n", "\n", $this_changelog));

		if (empty($this_changelog))
		{
			return '';
		}

		$notes = '';

		$params = ComponentHelper::getParams('com_ars');

		$generate_changelog = $params->get('begenchangelog', 1);
		$colorise_changelog = $params->get('becolorisechangelog', 1);

		if ($generate_changelog)
		{
			$notes .= '<ul>';

			foreach ($this_changelog as $line)
			{
				if (in_array($line, $first_changelog))
				{
					continue;
				}

				if ($colorise_changelog)
				{
					$notes .= '<li>' . $this->colorise($line) . "</li>\n";
				}
				else
				{
					$notes .= "<li>$line</li>\n";
				}
			}

			$notes .= '</ul>';
		}

		return $notes;
	}

	private function colorise(string $line): string
	{
		$line      = trim($line);
		$line_type = substr($line, 0, 1);

		switch ($line_type)
		{
			case '+':
				$style = 'added';
				$line  = trim(substr($line, 1));
				break;

			case '-':
				$style = 'removed';
				$line  = trim(substr($line, 1));
				break;

			case '#':
				$style = 'bugfix';
				$line  = trim(substr($line, 1));
				break;

			case '~':
				$style = 'minor';
				$line  = trim(substr($line, 1));
				break;

			case '!':
				$style = 'important';
				$line  = trim(substr($line, 1));
				break;

			default:
				$style = 'default';
				break;
		}

		return "<span class=\"ars-devrelease-changelog-$style\">$line</span>";
	}

	private function getReleaseFolder(string $folder, string $version, string $alias, string $maturity): ?string
	{
		$maturityLower = strtolower($maturity);
		$maturityUpper = strtoupper($maturity);

		$candidates = [
			$alias,
			$version,
			$version . '_' . $maturityUpper,
			$version . '_' . $maturityLower,
			$alias . '_' . $maturityUpper,
			$alias . '_' . $maturityLower,
		];

		foreach ($candidates as $candidate)
		{
			$folderCheck = $folder . '/' . $candidate;

			if (Folder::exists($folderCheck))
			{
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Deletes a BleedingEdge release.
	 *
	 * This method deletes the releases' items, their files, the log entries pointing to them, the folder of the BE
	 * release and the BE release itself.
	 *
	 * @param   ReleaseTable  $release
	 */
	private function recursiveDeleteRelease(ReleaseTable $release)
	{
		// Get the folder of the release
		$folder = $this->folder . '/' . (
				$this->getReleaseFolder($this->folder, $release->version, $release->alias, $release->maturity) ?? 'INVALID'
			);

		// Get the items
		$db         = $this->getDatabase();
		$release_id = $release->id;
		$query      = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__ars_items'))
			->where($db->quoteName('release_id') . ' = :relid')
			->bind(':relid', $release_id);

		/** @var ItemTable $item */
		$item = $this->getMVCFactory()->createTable('Item');

		$items = array_map(function ($data) use ($item) {
			$ret = clone $item;
			$ret->reset();
			$ret->bind($data);
			$ret->setUpdateModified(false);
			$ret->setUpdateCreated(false);

			return $ret;
		}, $db->setQuery($query)->loadAssocList() ?: []);

		// Delete log entries
		if (!empty($items))
		{
			$itemIds = array_map(function ($item) {
				return $item->getId();
			}, $items);

			$query = $db->getQuery(true)
				->delete($db->quoteName('#__ars_log'))
				->whereIn($db->quoteName('item_id'), $itemIds);
			$db->setQuery($query)->execute();
		}

		foreach ($items as $item)
		{
			// Delete the file
			if ($item->type == 'file')
			{
				$filePath = $folder . '/' . $item->filename;

				if (is_file($filePath))
				{
					if (!@unlink($filePath))
					{
						File::delete($filePath);
					}
				}
			}

			// Delete the item
			$item->delete();
		}

		// Delete the folder, recursively
		if (@is_dir($folder))
		{
			if (!@unlink($folder))
			{
				Folder::delete($folder);
			}
		}

		// Delete the release itself
		$release->delete();
	}
}