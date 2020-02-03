<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Update;


use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

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
		$dlid              = trim($this->input->getCmd('dlid', ''));

		if (!empty($dlid))
		{
			$dlid = Filter::reformatDownloadID($dlid);
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
	 * @param object|null $item
	 *
	 * @return array ['url', 'format']
	 */
	public function getDownloadUrl(?object $item): array
	{
		if (is_null($item))
		{
			return ['', ''];
		}

		$downloadURL = Router::_('index.php?option=com_ars&view=Item&task=download&format=raw&id=' .
			$item->item_id . $this->dlidRequest,
			true, Route::TLS_IGNORE, true);

		if ($item->itemtype == 'link')
		{
			$downloadURL = $item->url;
		}

		if (substr(strtolower($downloadURL), -4) == '.zip')
		{
			$format = 'zip';
		}
		elseif (substr(strtolower($downloadURL), -4) == '.tgz')
		{
			$format = 'tgz';
		}
		elseif (substr(strtolower($downloadURL), -7) == '.tar.gz')
		{
			$format = 'tgz';
		}
		elseif (substr(strtolower($downloadURL), -4) == '.tar')
		{
			$format = 'tar';
		}
		elseif (substr(strtolower($downloadURL), -8) == '.tar.bz2')
		{
			$format = 'tbz2';
		}
		elseif (substr(strtolower($downloadURL), -4) == '.tbz')
		{
			$format = 'tbz2';
		}
		elseif (substr(strtolower($downloadURL), -5) == '.tbz2')
		{
			$format = 'tbz2';
		}
		else
		{
			$fileNameParts = explode('.', $downloadURL);
			$format        = array_pop($fileNameParts);
		}

		if ($item->itemtype == 'link')
		{
			return [$downloadURL, $format];
		}

		$dlUri = Uri::getInstance($downloadURL);

		if (Factory::getConfig()->get('sef_suffix', 0) == 1)
		{
			$pathParts = explode('.', $dlUri->getPath());

			if ((count($pathParts) > 1) && (array_pop($pathParts) == 'raw'))
			{
				$dlUri->setPath(implode('.', $pathParts));
			}
		}

		$dlUri->setVar('format', 'raw');
		$dlUri->setVar('dummy', 'my.' . $format);

		return [$dlUri->toString(), $format];
	}

	public function getParsedPlatforms(?object $item): array
	{
		$parsedPlatforms = [
			'platforms' => [],
			'php'       => [],
		];

		$jVersion = new Version();

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
					$platforms[] = $this->envs[$eid]->xmltitle;
				}
			}

			if (empty($platforms))
			{
				$platforms = [
					'joomla/' . $jVersion->RELEASE,
					'php/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
				];
			}
		}
		else
		{
			$platforms = [
				'joomla/' . $jVersion->RELEASE,
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

		return $parsedPlatforms;
	}
}