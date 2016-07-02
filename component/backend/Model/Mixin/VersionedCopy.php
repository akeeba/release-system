<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model\Mixin;

defined('_JEXEC') or die;

/**
 * Trait for creating item copies with versioned titles such as "Something", "Something (1)", "Something (2)" etc
 */
trait VersionedCopy
{
	protected function onBeforeCopy()
	{
		/** @var \FOF30\Model\DataModel $this */

		// If the old title is versioned, remove the copy number
		$oldTitle = $this->title;
		$parts = explode(' (', $oldTitle);

		if (count($parts))
		{
			$lastPart = array_pop($parts);

			if (substr($lastPart, -1) == ')')
			{
				$lastPart = substr($lastPart, 0, -1);

				if (is_numeric($lastPart))
				{
					$oldTitle = implode(' (', $parts);
				}
			}
		}

		// Get all titles which are like ours plus a version string
		$db    = $this->getDBO();
		$query = $db->getQuery(true)
					->select($db->qn($this->getFieldAlias('title')))
					->from($db->qn($this->getTableName()))
					->where($db->qn($this->getFieldAlias('title')) . ' LIKE ' . $db->q($oldTitle . ' (%)'))
					->order($db->qn($this->getKeyName()) . ' ASC');

		$db->setQuery($query);
		$titles = $db->loadColumn();
		$lastVersion = 1;

		// If we have versioned titles take the number from the last one and increment it by one
		if (!empty($titles))
		{
			$title = array_pop($titles);
			$parts = explode(' (', $title);
			$lastVersion = (int) rtrim(array_pop($parts), ')');
			$lastVersion++;
		}

		$this->title = $oldTitle . ' (' . $lastVersion . ')';

		// Also reset the alias and the created / modified / locked fields
		$this->alias = '';
		$this->locked_by = 0;
		$this->locked_on = $this->getDbo()->getNullDate();
		$this->created_by = 0;
		$this->created_on = $this->locked_on;
		$this->modified_by = 0;
		$this->modified_on = $this->locked_on;
	}
}