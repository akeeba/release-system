<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\ARS\Site\Model\EnvironmentsModel;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

class JsonView extends \Joomla\CMS\MVC\View\JsonView
{
	use Common;
	use ViewTaskBasedEventsTrait;

	public $items = [];

	public $published = false;

	public $category = 0;

	public $envs = [];

	public $showChecksums = false;

	public $filteredItemIDs = null;

	protected function onBeforeJson(): void
	{
		$this->commonSetup();

		/** @var EnvironmentsModel $envModel */
		$envModel = $this->getModel('Environments');
		$params   = Factory::getApplication()->getParams('com_ars');

		$this->envs          = $envModel->getEnvironmentXMLTitles();
		$this->showChecksums = $params->get('show_checksums', 0) == 1;

		/** @var JsonDocument $document */
		$document = $this->document;
		$document->setMimeEncoding('application/json');

		// Extract unstable versions and only keep the very latest one
		$testingVersions = array_filter(
			$this->items,
			fn($item) => in_array($item->maturity, ['alpha', 'beta', 'rc'])
		);

		uasort($testingVersions, fn($a, $b) => version_compare($a->version, $b->version));
		$latestTesting  = empty($testingVersions) ? null : array_pop($testingVersions);
		$testingItemIDs = empty($testingVersions) ? [] : array_map(fn($x) => $x->item_id, $testingVersions);

		if (!empty($testingItemIDs))
		{
			$this->items = array_filter(
				$this->items,
				fn($x) => !in_array($x->item_id, $testingItemIDs)
			);
		}

		/**
		 * Use Version Compatibility information to cut down the number of displayed versions?
		 */
		if ($params->get('use_compatibility', 1) == 1)
		{
			$this->applyVersionCompatibilityUpdateStreamFilter();

			$this->items = array_filter(
				$this->items,
				fn($x) => in_array($x->release_id, $this->filteredItemIDs)
			);
		}

		// If we have a testing release, and it's newer than the latest stable, add it to the list.
		if (!empty($latestTesting))
		{
			$latestStable = array_slice($this->items, 0, 1);

			if (!empty($latestStable) && version_compare($latestTesting->version, $latestStable[0]->version, 'gt'))
			{
				array_unshift($this->items, $latestTesting);
			}
		}

		$minify = Factory::getApplication()->getParams('com_ars')->get('minify_xml', 1) == 1;

		echo json_encode(
			array_values(
				array_map(
					function (object $item) use ($document, $minify) {
						$document->setName(ApplicationHelper::stringURLSafe($item->cat_title));

						$parsedPlatforms = $this->getParsedPlatforms($item, false, false);
						[$downloadUrl, $format] = $this->getDownloadUrl($item);
						$minPhp     = array_reduce(
							$parsedPlatforms['php'],
							function (?string $carry, ?string $item): ?string {
								if (empty($carry))
								{
									return $item;
								}

								return version_compare($item, $carry, 'lt') ? $item : $carry;
							}, null
						);
						$maxPhp     = array_reduce(
							$parsedPlatforms['php'],
							function (?string $carry, ?string $item): ?string {
								if (empty($carry))
								{
									return $item;
								}

								return version_compare($item, $carry, 'gt') ? $item : $carry;
							}, null
						);
						$byPlatform = [];
						foreach ($parsedPlatforms['platforms'] as $platform)
						{
							[$pType, $pVersion] = $platform;
							$byPlatform[$pType]   = $byPlatform[$pType] ?? [];
							$byPlatform[$pType][] = $pVersion;
						}

						$item = (object) [
							'name'           => $item->name,
							'version'        => $item->version,
							'date'           => (new Date($item->created))->format('Y-m-d'),
							'infoUrl'        => Route::_(
								'index.php?option=com_ars&view=items&release_id=' . $item->release_id . '&category_id='
								. $item->category,
								true, Route::TLS_IGNORE, true
							),
							'download'       => $downloadUrl,
							'releaseNotes'   => $item->release_notes,
							'downloadFormat' => $format,
							'maturity'       => $item->maturity,
							'platforms'      => $byPlatform,
							'php'            => $parsedPlatforms['php'],
							'phpMin'         => $minPhp,
							'phpMax'         => $maxPhp,
							'checksum'       => [
								'md5'    => $item->md5 ?? null,
								'sha1'   => $item->sha1 ?? null,
								'sha256' => $item->sha256 ?? null,
								'sha384' => $item->sha384 ?? null,
								'sha512' => $item->sha512 ?? null,
							],
						];

						if ($minify)
						{
							$item->releaseNotes = null;

							unset($item->checksum);
						}
						else
						{
							$item->checksum = array_filter($item->checksum);

							if (empty($item->checksum))
							{
								unset($item->checksum);
							}
						}

						return $item;

					},
					$this->items
				)
			), (defined('JDEBUG') && JDEBUG) ? JSON_PRETTY_PRINT : 0
		);
	}
}