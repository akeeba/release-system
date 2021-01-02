<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Exception;
use FOF30\Date\Date;
use FOF30\Model\DataModel\Collection;
use FOF30\Model\Model;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;

class BleedingEdge extends Model
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
	 * @var  Categories
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
	 * @param   Categories  $category  The category to scan
	 *
	 * @return  void
	 * @throws Exception
	 */
	public function scanCategory(Categories $category): void
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

		// Can't proceed if it's not a bleedingedge category
		if ($this->category->type != 'bleedingedge')
		{
			return;
		}

		// We will now prune releases based on the existence of their files, their age and their count.
		$known_folders = [];
		$releases      = $category->releases;

		if (!is_object($releases))
		{
			// This makes sure that a category without releases won't cause an error
			$releases = new Collection();
		}

		// Releases pointing to non-existent folders will be deleted
		$toDelete = $releases->filter(function (Releases &$release) use (&$known_folders) {
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
		if ($toDelete->count())
		{
			$releases = $releases->diff($toDelete);
		}

		// Apply maximum age limits
		$ageLimit = $this->container->params->get('bleedingedge_age', 0);

		if ($ageLimit > 0)
		{
			// Releases older than this timestamp are to be deleted
			$targetTimestamp = time() - (86400 * $ageLimit);

			// Find which BleedingEdge releases I need to delete by age
			$toDelete = $toDelete->merge($releases->filter(function (Releases $release) use ($targetTimestamp) {
				try
				{
					return (new Date($release->created))->getTimestamp() <= $targetTimestamp;
				}
				catch (Exception $e)
				{
					// The release creation timestamp is invalid. Delete the sucker anyway.
					return true;
				}
			}));

			// Keep the releases which are not already marked for deletion
			if ($toDelete->count())
			{
				$releases = $releases->diff($toDelete);
			}
		}

		// Apply count limits
		$countLimit = $this->container->params->get('bleedingedge_count', 0);

		if (($countLimit > 0) && ($releases->count() > $countLimit))
		{
			// Add the excess releases in the collection of releases to remove
			$toDelete = $toDelete->merge($releases->slice($countLimit));
			// Conversely, only keep as many releases as I was told to keep
			$releases = $releases->take($countLimit);
		}

		// Remove any leftover releases
		$toDelete->each(function (Releases $release) {
			$this->recursiveDeleteRelease($release);
		});

		// Sort releases in ascending order
		$releases->sort(function (Releases $a, Releases $b) {
			return $a->getId() <=> $b->getId();
		});

		// Get the latest release, used to calculate the CHANGELOG
		$first_release   = $releases->last();
		$first_changelog = [];

		/** @var Releases $first_release */
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

					$jNow = new Date();

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
					/** @var Releases $table */
					$release = $this->container->factory->model('Releases')->tmpInstance();

					try
					{
						$release->create($data);
						$this->checkFiles($release);
					}
					catch (Exception $e)
					{
					}
				}
			}
		}
	}

	public function checkFiles(Releases $release): void
	{
		if (!$release->id)
		{
			return;
			//throw new \LogicException('Unexpected empty release identifier in BleedingEdge::checkFiles()');
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

		$known_folders[] = $folderName;
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

				$release->save();
			}
		}

		$release->getRelations()->rebase($release);

		$known_items = [];

		$files = Folder::files($folder);

		if ($release->items->count())
		{
			/** @var Items $item */
			foreach ($release->items as $item)
			{
				$known_items[] = basename($item->filename);

				if ($item->published && !in_array(basename($item->filename), $files))
				{
					$item->unpublish();
				}

				if (!$item->published && in_array(basename($item->filename), $files))
				{
					$item->publish();
				}
			}
		}

		if (!empty($files))
		{
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

				$jNow = new Date();
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

				if (isset($data['ignore']))
				{
					if ($data['ignore'])
					{
						continue;
					}
				}

				/** @var Items $table */
				$table = $this->container->factory->model('Items')->tmpInstance();
				$table->create($data);
			}
		}

		if (isset($table) && is_object($table) && method_exists($table, 'reorder'))
		{
			$db = $table->getDbo();
			$table->reorder($db->qn('release_id') . ' = ' . $db->q($release->id));
		}
	}

	/**
	 * Sets the category we are operating on
	 *
	 * @param   Categories|integer  $catId  A category table or a numeric category ID
	 *
	 * @return void
	 */
	protected function setCategory(int $catId): void
	{
		// Initialise
		$this->folder      = null;
		$this->category_id = (int) $catId;
		$this->category    = $this->container->factory->model('Categories')->tmpInstance();
		$this->category->find($this->category_id);

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
	 * @param   Releases  $release
	 */
	private function recursiveDeleteRelease(Releases $release)
	{
		// Get the folder of the release
		$folder = $this->folder . '/' . (
				$this->getReleaseFolder($this->folder, $release->version, $release->alias, $release->maturity) ?? 'INVALID'
			);

		/** @var Logs $logModel */
		$logModel = $this->container->factory->model('Logs')->tmpInstance();

		$this->container->factory->model('Items')
			->tmpInstance()
			->release_id($release->getId())
			->get(false)
			->each(function (Items $item) use ($logModel, $folder) {
				// Delete log entries for this item
				$logModel->setState('item_id', $item->getId());
				$logModel->get(true)->delete();

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
			});

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
