<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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
	AND r.published = 1
	AND c.published = 1
ORDER BY
	u.id ASC, i.`created` DESC
ENDQUERY;
		$db->setQuery($query);
		$rawItems = $db->loadObjectList();
		
		$this->items = array();
		$ids = array();
		
		if(!empty($rawItems)) foreach($rawItems as $item) {
			$id = $item->id;
			if(in_array($id, $ids)) continue;
			$this->items[] = $item;
		}
	}

	function getItems($id)
	{
		$db = $this->getDBO();
		$esc_id = $db->Quote($id);
		$query = <<<ENDQUERY
SELECT
    `u`.*, `i`.`id` as `item_id`, `i`.`environments` as `environments`,
	`r`.`version`, `r`.`maturity`,
    `c`.`title` as `cat_title`, `i`.`release_id`,
    `i`.`filename`, `i`.`url`, `i`.`type` as `itemtype`, `r`.`created`,
	`r`.`notes` as `release_notes`
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
	AND r.published = 1
	AND c.published = 1	
ORDER BY
	r.`created` DESC
ENDQUERY;
		$db->setQuery($query);
		$this->items = $db->loadObjectList();
	}
}