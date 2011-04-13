<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
    u.*, `i`.`id` as `item_id`, `r`.`version`, `r`.`maturity`
FROM
	(
		`#__ars_items` as `i`
		INNER JOIN `#__ars_releases` AS `r` ON(`r`.`id` = `i`.`release_id`)
		INNER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `r`.`category_id`)
	)
	LEFT OUTER JOIN #__ars_updatestreams AS u ON(u.id = `i`.`updatestream`)
WHERE
	u.type = $esc_category
	AND u.published = 1
	AND i.published = 1
GROUP BY
	u.id
ORDER BY
	u.id ASC, i.`created` DESC
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
    `u`.*, `i`.`id` as `item_id`, `r`.`version`, `r`.`maturity`,
    `c`.`title` as `cat_title`, `i`.`release_id`,
    `i`.`filename`, `i`.`url`, `i`.`type` as `itemtype`, `r`.`created`
FROM
  (
    `#__ars_items` as `i`
    INNER JOIN `#__ars_releases` AS `r` ON(`r`.`id` = `i`.`release_id`)
    INNER JOIN `#__ars_categories` AS `c` ON(`c`.`id` = `r`.`category_id`)
  )
  RIGHT JOIN #__ars_updatestreams AS u ON(u.id = i.updatestream)
WHERE
	u.id = $esc_id
	AND u.published = 1
	AND i.published = 1
ORDER BY
	r.`created` DESC
ENDQUERY;
		$db->setQuery($query);
		$this->items = $db->loadObjectList();
	}
}