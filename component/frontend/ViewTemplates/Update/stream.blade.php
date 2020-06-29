<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Site\Helper\Router as RouterHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

$showChecksums = isset($this->showChecksums) ? $this->showChecksums : false;

$streamTypeMap = [
	'components' => 'component',
	'libraries'  => 'library',
	'modules'    => 'module',
	'packages'   => 'package',
	'plugins'    => 'plugin',
	'files'      => 'file',
	'templates'  => 'template',
];

$updateStream = new SimpleXMLElement("<updates />");

/** @var \Akeeba\ReleaseSystem\Site\Model\UpdateStreams $item */
foreach ($this->items as $item)
{
	$parsedPlatforms = $this->getParsedPlatforms($item);
	foreach ($parsedPlatforms['platforms'] as $platform)
	{
		list($platformName, $platformVersion) = $platform;
		list($downloadUrl, $format) = $this->getDownloadUrl($item);
		$minPhp = array_reduce($parsedPlatforms['php'], function (?string $carry, ?string $item): ?string {
			if (empty($carry))
			{
				return $item;
			}

			return version_compare($item, $carry, 'lt') ? $item : $carry;
		}, null);

		$update = $updateStream->addChild('update');

		$update->addChild('name', $item->name);
		$update->addChild('description', $item->name);
		$update->addChild('element', $item->element);
		$update->addChild('type', $streamTypeMap[$item->type]);
		$update->addChild('version', $item->version);

		$infoUrl = $update->addChild('infourl', RouterHelper::_(
			'index.php?option=com_ars&view=Items&release_id=' . $item->release_id,
			true, Route::TLS_IGNORE, true
		));
		$infoUrl->addAttribute('title', sprintf('%s %s', $item->cat_title, $item->version));

		$downloads = $update->addChild('downloads');

		$dl = $downloads->addChild('downloadurl', $downloadUrl);
		$dl->addAttribute('type', 'full');
		$dl->addAttribute('format', $format);

		$tags = $update->addChild('tags');

		$tags->addChild('tag', $item->maturity);

		$update->addChild('maintainer', $this->container->platform->getConfig()->get('sitename'));
		$update->addChild('maintainerurl', Uri::base());
		$update->addChild('section', 'Updates');

		$targetPlatform = $update->addChild('targetplatform');
		$targetPlatform->addAttribute('name', $platformName);
		$targetPlatform->addAttribute('version', $platformVersion);

		foreach (['md5', 'sha1', 'sha256', 'sha384', 'sha512'] as $checksum)
		{
			if ($showChecksums && !empty($item->{$checksum}))
			{
				$checksum = $update->addChild($checksum, $item->{$checksum});
			}
		}

		if (($platformName == 'joomla') && (substr($platformVersion, 0, 2) != '1.'))
		{
			$update->addChild('client_id', (int) $item->client_id);
		}
        elseif ($platformName == 'joomla')
		{
			$update->addChild('client', (int) $item->client_id);
		}

		if (!empty($item->folder))
		{
			$update->addChild('folder', $item->folder);
		}

		foreach ($parsedPlatforms['php'] as $phpVersion)
		{
			$update->addChild('ars-phpcompat', $phpVersion);
		}

		if (!empty($minPhp))
		{
			$update->addChild('php_minimum', $minPhp);
		}
	}
}

echo $updateStream->asXML();