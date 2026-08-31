<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Helper;

defined('_JEXEC') || die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Database query factory, isolating the createQuery() compatibility branch.
 *
 * `DatabaseDriver::createQuery()` was added in Joomla 5.1. On older versions the equivalent is
 * `getQuery(true)`, which is deprecated on newer ones. Rather than repeat that choice at every
 * call site, every query in this package is built through here.
 *
 * There is a second reason to funnel through one method even once the floor is high enough that
 * the branch below is dead: `createQuery()` is only declared on `DatabaseInterface` from Joomla
 * 6.0. Most callers hold a `DatabaseInterface` — that is the key they resolve from the DI
 * container — so calling it directly is correct at runtime but unprovable to a static analyser on
 * Joomla 5.x. Confining that to one method confines the analyser's complaint to one method.
 *
 * When the minimum supported Joomla version reaches 5.1, this becomes a one-line method. That is
 * the point: raising the floor is then a single edit, not several dozen.
 *
 * @since  7.5.1
 */
final class DbQuery
{
	/**
	 * Create a new, empty query object.
	 *
	 * @param   DatabaseInterface  $db  The database driver to create the query for.
	 *
	 * @return  QueryInterface
	 * @since   7.5.1
	 */
	public static function create(DatabaseInterface $db): QueryInterface
	{
		return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
	}
}
