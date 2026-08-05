<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Stubs;

defined('_JEXEC') or die;

/**
 * A {@see RecordingDatabase} that also declares `createQuery()`.
 *
 * ARS branches on `method_exists($db, 'createQuery')` because that method only exists from Joomla
 * 5.1 onwards. `method_exists()` is answered by the class, not the instance, so the only way to
 * drive both branches is to hand the code two different classes — which is exactly what this one is
 * for.
 *
 * Pair it with {@see RecordingDatabase} in a data provider. A test that only ever runs against one
 * of them proves the branch it happened to take, and says nothing about the other.
 */
class ModernRecordingDatabase extends RecordingDatabase
{
	public function createQuery()
	{
		$query                  = new RecordingQuery();
		$this->createdQueries[] = $query;

		return $query;
	}
}
