#!/usr/bin/env php
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Fixes the environments of releases under a category.
 *
 * Use:
 * ./ars-fix-environments.php --csv=/path/to/file.csv --category=123 [--create-missing]
 *
 * It requires a CSV file with ';' as the separator and which has four or more columns:
 * - Release version
 * - (ignored; e.g. release date)
 * - PHP versions, comma-separated
 * - Joomla versions, comma-separated
 *
 * The first line of the CSV file is ignored. It is assumed it contains column headers.
 *
 * Empty lines are ignored as well.
 *
 * The --create-missing flag tells the script to create missing environments. For example, if there is no php/5.0
 * environment it will create a new one.
 */

use Akeeba\ReleaseSystem\Admin\Model\Categories;
use Akeeba\ReleaseSystem\Admin\Model\Environments;
use Akeeba\ReleaseSystem\Admin\Model\Items;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF40\Container\Container;
use FOF40\Model\DataModel\Exception\RecordNotLoaded;

// Setup and import the base CLI script
$minphp = '7.3.0';

// Boilerplate -- START
define('_JEXEC', 1);

foreach ([__DIR__, getcwd()] as $curdir)
{
	if (file_exists($curdir . '/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/defines.php';

		break;
	}

	if (file_exists($curdir . '/../includes/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/../includes/defines.php';

		break;
	}
}

defined('JPATH_LIBRARIES') || die ('This script must be placed in or run from the cli folder of your site.');

require_once JPATH_LIBRARIES . '/fof40/Cli/Application.php';

// Boilerplate -- END

class ArsFixEnvironments extends FOFApplicationCLI
{
	/**
	 * ARS environments
	 *
	 * It ends up having a nested array structure like this:
	 * ```
	 * [
	 *   'php' => [
	 *       '5.0' => 123,
	 *       '5.1' => 124,
	 *   ],
	 *   'joomla' => [
	 *       '1.5' => 210,
	 *       '1.6' => 211,
	 *   ]
	 * ]
	 * ```
	 *
	 * @var array
	 * @see self::populateEnvironments
	 */
	protected $environments = [];

	protected function doExecute()
	{
		$catId  = $this->input->getInt('category');
		$inFile = $this->input->getPath('csv');

		$this->banner();
		$this->sanityChecks($catId, $inFile);
		$this->populateEnvironments();

		$envMap    = $this->getReleaseToEnvironments($inFile);
		$container = Container::getInstance('com_ars', [], 'admin');
		/** @var Releases $relModel */
		$relModel = $container->factory->model('Releases')->tmpInstance();

		foreach ($envMap as $releaseId => $environments)
		{
			/** @var Releases $release */
			$release = $relModel->with(['items'])->findOrFail($releaseId);

			$this->out(sprintf('Version %s', $release->version));

			$release->items->each(function (Items $item) use ($environments) {
				$target                = $item->type == 'file' ? $item->filename : $item->url;
				$baseName              = basename($target);
				$effectiveEnvironments = array_merge($environments);

				if (!in_array(substr($baseName, 0, 4), ['com_', 'plg_', 'pkg_', 'file', 'lib_', 'tpl_', 'mod_']))
				{
					$effectiveEnvironments = [];
				}

				$this->out(sprintf("\t$baseName"));

				$db    = $item->getDbo();
				$query = $db->getQuery(true)
					->update($item->getTableName())
					->set($db->qn('environments') . ' = ' . $db->q(json_encode($effectiveEnvironments)))
					->where($db->qn($item->getKeyName()) . ' = ' . $db->q($item->getId()));
				$db->setQuery($query)->execute();
			});
		}
	}

	/**
	 * @param   string  $inFile
	 *
	 * @return array
	 */
	protected function getReleaseToEnvironments(string $inFile): array
	{
		$data       = $this->getDataFromFile($inFile);
		$versionMap = array_combine(array_keys($data), array_map([$this, 'getReleaseId'], array_keys($data)));
		$envMap     = [];

		foreach ($versionMap as $version => $releaseId)
		{
			if (empty($releaseId))
			{
				continue;
			}

			$envSections         = $data[$version];
			$releaseEnvironments = array_merge(
				array_map(function ($x) {
					return $this->mapEnvironment('php', $x);
				}, $envSections['php']),
				array_map(function ($x) {
					return $this->mapEnvironment('joomla', $x);
				}, $envSections['joomla']),
			);
			$releaseEnvironments = array_filter($releaseEnvironments, function ($x) {
				return !empty($x);
			});
			$envMap[$releaseId]  = array_unique($releaseEnvironments);
		}

		return $envMap;
	}

	/**
	 * Print a CLI application banner
	 */
	private function banner()
	{
		$year   = date('Y');
		$banner = <<< BANNER
Akeeba Release System – Environments Fixer
Copyright (c)2010-{$year} Nicholas K. Dionysopoulos / Akeeba Ltd
================================================================================

BANNER;
		$this->out($banner);
	}

	/**
	 * Makes sure we have valid-looking command line parameters
	 *
	 * @param   int     $catId
	 * @param   string  $inFile
	 */
	private function sanityChecks(int $catId, string $inFile): void
	{
		$container = Container::getInstance('com_ars', [], 'admin');

		if (empty($inFile) || !is_file($inFile))
		{
			throw new InvalidArgumentException("You need to specify the CSV file with the environment data with --csv=/path/to/file");
		}

		if (empty($catId))
		{
			throw new InvalidArgumentException('You must specify a category to fix with --category=123');
		}

		/** @var Categories $catModel */
		$catModel = $container->factory->model('Categories')->tmpInstance();
		$category = $catModel->findOrFail($catId);

		if ($category->getId() != $catId)
		{
			throw new RuntimeException(sprintf("Cannot find category %d", $catId));
		}
	}

	/**
	 * Gets the raw data from the CSV file
	 *
	 * @param   string  $inFile
	 *
	 * @return array
	 */
	private function getDataFromFile(string $inFile): array
	{
		$data = [];
		$fp   = @fopen($inFile, 'rt');

		if ($fp === false)
		{
			throw new RuntimeException(sprintf("Cannot open %s for reading", $inFile));
		}

		fgetcsv($fp, 0, ';');

		while (!feof($fp))
		{
			[$version, , $phpVersions, $joomlaVersions] = fgetcsv($fp, 0, ';');

			if (empty($version))
			{
				continue;
			}

			$data[$version] = [
				'php'    => array_map('trim', explode(',', $phpVersions)),
				'joomla' => array_map('trim', explode(',', $joomlaVersions)),
			];
		}

		fclose($fp);

		return $data;
	}

	/**
	 * Populates the ARS environments
	 */
	private function populateEnvironments(): void
	{
		$container          = Container::getInstance('com_ars', [], 'admin');
		$this->environments = [];
		/** @var Environments $envModels */
		$envModels = $container->factory->model('Environments')->tmpInstance();
		$envModels
			->enabled(1)
			->get(true)
			->each(function (Environments $env) {
				[$section, $version] = explode('/', $env->xmltitle);

				if (empty($version))
				{
					return;
				}

				$this->environments[$section]           = $this->environments[$section] ?? [];
				$this->environments[$section][$version] = $env->getId();
			});
	}

	private function getReleaseId(?string $version): ?int
	{
		$catId     = $this->input->getInt('category');
		$container = Container::getInstance('com_ars', [], 'admin');
		/** @var Releases $relModel */
		$relModel = $container->factory->model('Releases')->tmpInstance();
		try
		{
			$release = $relModel->findOrFail([
				'version'     => $version,
				'category_id' => $catId,
			]);
		}
		catch (RecordNotLoaded $e)
		{
			return null;
		}

		return $release->getId();
	}

	private function mapEnvironment(string $section, string $version): ?int
	{
		$id = $this->environments[$section][$version] ?? null;

		if (!empty($id))
		{
			return $id;
		}

		if (!$this->input->getBool('create-missing', false))
		{
			return null;
		}

		$container = Container::getInstance('com_ars', [], 'admin');
		/** @var Environments $envModel */
		$envModel = $container->factory->model('Environments')->tmpInstance();
		$envModel->create([
			'title'    => sprintf('%s %s', ($section === 'php') ? 'PHP' : ucfirst($section), $version),
			'xmltitle' => sprintf('%s/%s', $section, $version),
			'icon'     => '',
		]);

		$this->environments[$section][$version] = $envModel->getId();

		return $this->environments[$section][$version];
	}
}

FOFApplicationCLI::getInstance('ArsFixEnvironments')->execute();