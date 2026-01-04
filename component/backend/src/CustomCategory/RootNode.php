<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\CustomCategory;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class RootNode extends CategoryNode
{
	public function __construct($category = null, $constructor = null)
	{
		parent::__construct($category, $constructor);

		$this->_constructor = $constructor;
	}

	public function hasChildren()
	{
		return true;
	}

	public function &getChildren($recursive = false)
	{
		static $set = null;

		if ($set === null)
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			$query = $db->getQuery(true)
				->select('id')
				->from('#__ars_categories');

			$db->setQuery($query);
			$childrenIds = $db->loadColumn();

			$set = array_map(
				fn($id) => $this->_constructor->get($id),
				$childrenIds ?: []
			);
		}

		return $set;
	}
}