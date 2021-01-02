<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
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
		'AutoDescriptions' => \Akeeba\ReleaseSystem\Admin\Model\AutoDescriptions::class,
		'Categories'       => \Akeeba\ReleaseSystem\Admin\Model\Categories::class,
		'ControlPanel'     => \Akeeba\ReleaseSystem\Admin\Model\ControlPanel::class,
		'DownloadIDLabels' => \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels::class,
		'Environments'     => \Akeeba\ReleaseSystem\Admin\Model\Environments::class,
		'Items'            => \Akeeba\ReleaseSystem\Admin\Model\Items::class,
		'Logs'             => \Akeeba\ReleaseSystem\Admin\Model\Logs::class,
		'Releases'         => \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams::class,
	]));
}