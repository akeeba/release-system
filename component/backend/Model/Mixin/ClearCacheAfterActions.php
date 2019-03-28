<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\Mixin;

use FOF30\Utils\CacheCleaner;

trait ClearCacheAfterActions
{
	protected function onAfterCopy($result)
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterSave()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterReorder()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterMove()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterArchive()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterTrash($id)
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterPublish()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterUnpublish()
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterRestore($id)
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	protected function onAfterDelete(&$id)
	{
		CacheCleaner::clearCacheGroups(['com_ars']);
	}
}