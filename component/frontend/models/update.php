<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

class ArsModelUpdate extends JModel
{
	public $items;

	function getCategoryItems($category)
	{
		$db = $this->getDBO();
		$esc_category = $db->Quote($category);
		$query = <<<ENDQUERY
SELECT
	u.*, i.id as `item_id`, i.version, i.maturity
FROM
	#__ars_view_items AS i
	LEFT OUTER JOIN jos_ars_updatestreams AS u ON(u.id = i.updatestream)
WHERE
	u.type = $esc_category
	AND u.published = 1
	AND i.published = 1
GROUP BY
	u.id
ORDER BY
	u.id ASC, i.`created` DESC
LIMIT 0,1
ENDQUERY;
		$db->setQuery($query);
		$this->items = $db->loadObjectList();
	}

	function getItems($id)
	{
		$db = $this->getDBO();
		$esc_id = $db->Quote($id);
		$query = <<<ENDQUERY
SELECT
	u.*, i.id as `item_id`, i.version, i.maturity, i.cat_title, i.release_id,
	i.filename, i.url, i.type, i.created
FROM
	#__ars_view_items AS i
	LEFT OUTER JOIN jos_ars_updatestreams AS u ON(u.id = i.updatestream)
WHERE
	u.id = $esc_id
	AND u.published = 1
	AND i.published = 1
ORDER BY
	i.`created` DESC
LIMIT 0,1
ENDQUERY;
		$db->setQuery($query);
		$this->items = $db->loadObjectList();
	}
}