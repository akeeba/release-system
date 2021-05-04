<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table\Mixin;

use Akeeba\Component\ARS\Administrator\Helper\CacheCleaner;

defined('_JEXEC') or die;

trait ClearCacheAfterActions
{
	/**
	 * Clear the component's cache after saving a record.
	 *
	 * @param   bool  $result       Did the record store successfully?
	 * @param   bool  $updateNulls  Was I asked to update null values in the record?
	 *
	 * @throws \Exception
	 */
	protected function onAfterStore($result, $updateNulls)
	{
		if (!$result)
		{
			return;
		}

		CacheCleaner::clearCacheGroups(['com_ars']);
	}

	/**
	 * Clear the component's cache after deleting a record.
	 *
	 * @param   bool   $result  Did the record get deleted successfully?
	 * @param   mixed  $pk      The primary key(s) of the deleted record
	 *
	 * @throws \Exception
	 */
	protected function onAfterDelete($result, $pk)
	{
		if (!$result)
		{
			return;
		}

		CacheCleaner::clearCacheGroups(['com_ars']);
	}
}