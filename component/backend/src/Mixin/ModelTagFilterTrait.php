<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die();

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Applies the `filter.tag` list filter to a query, joining Joomla's content item tag map.
 *
 * Shared by the Categories and Releases list models, which filter identical tag maps differing only
 * in their type alias and in the aliased column the map joins against.
 *
 * @since   7.5.0
 */
trait ModelTagFilterTrait
{
	/**
	 * Add the tag filter to a list query.
	 *
	 * Malformed input is discarded rather than pushed into the query: anything which is not a
	 * positive integer tag ID is dropped, and if nothing survives that the filter is not applied at
	 * all. That matters because the filter's value comes from `getUserStateFromRequest()`, so a
	 * value which makes the query fail keeps failing on every subsequent request in the same
	 * session, including ones which do not mention the tag filter.
	 *
	 * @param   QueryInterface  $query      The query to modify.
	 * @param   mixed           $tag        The `filter.tag` state value: a tag ID, or an array of them.
	 * @param   string          $typeAlias  The UCM type alias of the tagged content, e.g. `com_ars.category`.
	 * @param   string          $idColumn   The already aliased column the tag map joins against, e.g. `c.id`.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	protected function applyTagFilter(QueryInterface $query, $tag, string $typeAlias, string $idColumn): void
	{
		/** @var DatabaseInterface $db */
		$db = $this->getDatabase();

		// Keep only positive integer tag IDs. Anything else could only ever match no rows.
		$tagIds = array_values(
			array_unique(
				array_filter(
					array_map('intval', (array) $tag),
					fn(int $id): bool => $id > 0
				)
			)
		);

		if (empty($tagIds))
		{
			return;
		}

		// Run a simplified query when filtering by one tag.
		if (count($tagIds) === 1)
		{
			$tagId = array_pop($tagIds);

			$query->join(
				'INNER',
				$db->quoteName('#__contentitem_tag_map', 'tagmap'),
				$db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName($idColumn)
			)
				->where($db->quoteName('tagmap.type_alias') . ' = ' . $db->quote($typeAlias))
				->where($db->quoteName('tagmap.tag_id') . ' = :tagId')
				->bind(':tagId', $tagId, ParameterType::INTEGER);

			return;
		}

		/**
		 * Several tags: join a DISTINCT subquery, so a record tagged with more than one of them is
		 * still returned exactly once.
		 *
		 * The IN list is bound against $query, NOT against the subquery. Bound parameters do not
		 * travel with a subquery cast to a string — DatabaseQuery::getBounded() only ever returns the
		 * query object's own bindings — so placeholders inlined from a subquery would reach the
		 * driver unbound, and the whole list view would fail with "No data supplied for parameters in
		 * prepared statement".
		 */
		$placeholders = $query->bindArray($tagIds, ParameterType::INTEGER);

		$subQuery = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('DISTINCT ' . $db->quoteName('content_item_id'))
			->from($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('tag_id') . ' IN (' . implode(',', $placeholders) . ')')
			->where($db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias));

		$query->join(
			'INNER',
			'(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
			$db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName($idColumn)
		);
	}
}
