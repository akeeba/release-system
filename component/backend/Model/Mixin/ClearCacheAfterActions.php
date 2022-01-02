<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\Mixin;

use FOF40\Model\DataModel;
use FOF40\JoomlaAbstraction\CacheCleaner;

trait ClearCacheAfterActions
{
	protected function onAfterCopy(DataModel $result): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterSave(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterReorder(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterMove(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterArchive(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterTrash(&$id): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterPublish(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterUnpublish(): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterRestore($id): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterDelete(&$id): void
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}
}