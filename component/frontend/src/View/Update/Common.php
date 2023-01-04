<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Update;

use Akeeba\Component\ARS\Site\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

defined('_JEXEC') or die;

trait Common
{
	/**
	 * The request suffix to add to a download URL
	 *
	 * If no sanitized Download ID is found this is empty. Otherwise it's in the form of `&dlid=foobar` where foobar is
	 * the sanitized Download ID.
	 *
	 * @var string
	 */
	public $dlidRequest;

	private function commonSetup()
	{
		// Set up the download ID request suffix
		$this->dlidRequest = '';
		$input             = Factory::getApplication()->input;
		$dlid              = trim($input->getCmd('dlid', ''));

		if (!empty($dlid))
		{
			/** @var ItemModel $itemModel */
			$itemModel = $this->getModel('item');
			$dlid      = $itemModel->reformatDownloadID($dlid);
		}

		if (!empty($dlid))
		{
			$this->dlidRequest = '&dlid=' . $dlid;
		}
	}

	/**
	 * Return a sanitized download URL for a download item.
	 *
	 * This is necessary because Joomla! 3.2 and later handle all URLs and set the CMS "format" based on the URL suffix.
	 * For example, the SEF URL https://www.example.com/my/downloads/foobar.zip would be parsed as though it had
	 * `format=zip` in it. However, all download URLs are meant to be format=raw. This causes some interesting anomalies
	 * because now FOF is not aware this is supposed to be a raw view and the download fails.
	 *
	 * @param   object|null  $item
	 *
	 * @return array ['url', 'format']
	 */
	public function getDownloadUrl(?object $item): array
	{
		if (is_null($item))
		{
			return ['', ''];
		}

		$basename = basename($item->itemtype == 'file' ? $item->filename : $item->url);
		$lastDot  = strrpos($basename, '.');
		$format   = 'raw';

		if ($lastDot !== false)
		{
			$format = substr($basename, $lastDot + 1);
		}

		if (substr(strtolower($basename), -7) == '.tar.gz')
		{
			$format = 'tgz';
		}
		elseif (substr(strtolower($basename), -8) == '.tar.bz2')
		{
			$format = 'tbz2';
		}
		elseif (substr(strtolower($basename), -4) == '.tbz')
		{
			$format = 'tbz2';
		}
		elseif (substr(strtolower($basename), -5) == '.tbz2')
		{
			$format = 'tbz2';
		}

		$downloadURL = Route::_(sprintf(
			"index.php?option=com_ars&view=item&format=%s&category_id=%d&release_id=%d&item_id=%d%s",
			$format, $item->category, $item->release_id, $item->item_id, $this->dlidRequest
		),
			true, Route::TLS_IGNORE, true);

		if ($item->itemtype == 'link')
		{
			$downloadURL = $item->url;
		}

		if ($item->itemtype == 'link')
		{
			return [$downloadURL, $format];
		}

		$dlUri = Uri::getInstance($downloadURL);
		$app   = Factory::getApplication();

		if ($app->get('sef_suffix', 0) == 1)
		{
			$pathParts = explode('.', $dlUri->getPath());

			if ((count($pathParts) > 1) && (array_pop($pathParts) == 'raw'))
			{
				$dlUri->setPath(implode('.', $pathParts));
			}
		}

		return [htmlentities($dlUri->toString()), $format];
	}

	/**
	 * Parses the environments into distinct arrays of versions by platform name.
	 *
	 * When $compact is set to true (default) the versions for each platform are compacted into a single regular
	 * expression. This RegEx is understood by Joomla's update code and lets us create a concise update XML stream
	 * document with a single element for all supported versions of the CMS (as opposed to an update element for each
	 * supported CMS version). This may look silly but it saves a ton of money when delivering these updates through
	 * an Amazon CloudFlare CDN which charges per byte transferred.
	 *
	 * @param   object|null  $item     Update stream
	 * @param   bool         $compact  Should I compact the versions into a single RegEx?
	 *
	 * @return  array
	 */
	public function getParsedPlatforms(?object $item, bool $compact = true, bool $liar = false): array
	{
		$parsedPlatforms = [
			'platforms' => [],
			'php'       => [],
		];

		if (is_null($item))
		{
			return $parsedPlatforms;
		}

		/**
		 * DO NOT REMOVE -- DO NOT REFACTOR -- DO NOT TOUCH
		 *
		 * This is a virtual property. is_array() always returns false on it, breaking the code below
		 */
		$environments = $item->environments;

		if (!empty($environments) && is_array($environments))
		{
			$platforms = [];

			foreach ($environments as $eid)
			{
				if (isset($this->envs[$eid]))
				{
					$platforms[] = $this->envs[$eid];
				}
			}

			if (empty($platforms))
			{
				$platforms = [
					'joomla/' . Version::MAJOR_VERSION . '.' . Version::MINOR_VERSION,
					'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
				];
			}
		}
		else
		{
			$platforms = [
				'joomla/' . Version::MAJOR_VERSION . '.' . Version::MINOR_VERSION,
				'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
			];
		}

		foreach ($platforms as $platform)
		{
			$platformParts = explode('/', $platform, 2);

			switch (count($platformParts))
			{
				case 1:
					$platformName    = 'joomla';
					$platformVersion = $platformParts[0];
					break;
				default:
					$platformName    = $platformParts[0];
					$platformVersion = $platformParts[1];
					break;
			}

			if (strtolower($platformName) == 'php')
			{
				$parsedPlatforms['php'][] = $platformVersion;

				continue;
			}

			$parsedPlatforms['platforms'][] = [$platformName, $platformVersion];
		}

		$hasJoomla = array_reduce(
			$parsedPlatforms['platforms'],
			function (bool $carry, $item)
			{
				return $carry || $item[0] === 'joomla';
			},
			false
		);

		if ($hasJoomla && $liar)
		{
			$morePlatforms = [];
			$majorVersions = [];

			foreach ($parsedPlatforms['platforms'] as $item)
			{
				[$major, $minor] = explode('.', $item[1]);
				$majorVersions[] = $major;
				// Pretend that we support the next minor version of whatever is listed
				$morePlatforms[] = ['joomla', sprintf('%d.%d', $major, $minor + 1)];
			}

			// Get the minimum major version.
			asort($majorVersions);
			$major = array_shift($majorVersions);

			// Pretend that we support minor versions 0–9 of the minimum major version + 1.
			for ($i = 0; $i <= 10; $i++)
			{
				$morePlatforms[] = ['joomla', sprintf('%d.%d', $major + 1, $i)];
			}

			// Merge the real and pretend platforms, only keeping the unique items
			$parsedPlatforms['platforms'] = array_merge($parsedPlatforms['platforms'], $morePlatforms);
			$parsedPlatforms['platforms'] = array_unique($parsedPlatforms['platforms'], SORT_REGULAR);
		}

		if (!$compact)
		{
			return $parsedPlatforms;
		}

		/**
		 * At this point I have Joomla platforms set up as e.g. 3.8, 3.9, 3.10, 4.0. Ths would cause four identical
		 * entries to be output, only difference being the target platform.
		 */

		$platformVersions = [];

		foreach ($parsedPlatforms['platforms'] as $platform)
		{
			[$pName, $pVersion] = $platform;
			$platformVersions[$pName]   = $platformVersions[$pName] ?? [];
			$platformVersions[$pName][] = $pVersion;
		}

		$parsedPlatforms['platforms'] = [];

		foreach ($platformVersions as $pName => $pVersions)
		{
			$parsedPlatforms['platforms'][] = [
				$pName,
				$this->platformVersionCompactor($pVersions),
			];
		}

		return $parsedPlatforms;
	}

}