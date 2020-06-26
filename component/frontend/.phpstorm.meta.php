<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * phpStorm metadata
 *
 * @see https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html#create-metadata-files-inside-your-project
 */

namespace PHPSTORM_META
{
	override(\FOF30\Factory\FactoryInterface::model(), map([
		'AutoDescriptions' => \Akeeba\ReleaseSystem\Site\Model\AutoDescriptions::class,
		'Categories'       => \Akeeba\ReleaseSystem\Site\Model\Categories::class,
		'ControlPanel'     => \Akeeba\ReleaseSystem\Site\Model\ControlPanel::class,
		'DownloadIDLabels' => \Akeeba\ReleaseSystem\Site\Model\DownloadIDLabels::class,
		'Environments'     => \Akeeba\ReleaseSystem\Site\Model\Environments::class,
		'Items'            => \Akeeba\ReleaseSystem\Site\Model\Items::class,
		'Logs'             => \Akeeba\ReleaseSystem\Site\Model\Logs::class,
		'Releases'         => \Akeeba\ReleaseSystem\Site\Model\UpdateStreams::class,
	]));

	override(\ArsRouter::getModelObject(), map([
		'Categories' => \Akeeba\ReleaseSystem\Site\Model\Categories::class,
		'Releases'   => \Akeeba\ReleaseSystem\Site\Model\UpdateStreams::class,
		'Items'      => \Akeeba\ReleaseSystem\Site\Model\Items::class,
	]));
}