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

use Akeeba\Component\ARS\Administrator\Extension\ArsComponent;
use Akeeba\Component\ARS\Administrator\Table\CategoryTable;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class LeafNode extends CategoryNode
{
	public function __construct(int $id, $constructor = null)
	{
		/** @var ArsComponent $component */
		$component = Factory::getApplication()->bootComponent('com_ars');
		/** @var CategoryTable $table */
		$table = $component->getMVCFactory()->createTable('Category', 'Administrator');
		$table->load($id);

		$category = [
			'id'               => $table->getId(),
			'asset_id'         => $table->asset_id,
			'parent_id'        => 0,
			'lft'              => $table->getId(),
			'rgt'              => PHP_INT_MAX - ($table->getId() ?? 0),
			'level'            => 1,
			'extension'        => 'com_ars',
			'title'            => $table->title,
			'alias'            => $table->alias,
			'description'      => $table->description,
			'published'        => $table->published,
			'checked_out'      => $table->checked_out,
			'checked_out_time' => $table->checked_out_time,
			'access'           => $table->access,
			'params'           => '{}',
			'metadesc'         => null,
			'metakey'          => null,
			'metadata'         => null,
			'created_user_id'  => $table->created_by,
			'created_time'     => $table->created,
			'modified_user_id' => $table->modified_by,
			'modified_time'    => $table->modified,
			'hits'             => 0,
			'language'         => $table->language ?: '*',
			'numitems'         => null,
			'slug'             => $table->alias,
		];

		parent::__construct($category, $constructor);
	}

	public function hasChildren()
	{
		return false;
	}

	public function &getChildren($recursive = false)
	{
		$set = [];

		return $set;
	}

	public function getParent()
	{
		return $this->_constructor->get('root');
	}

	public function getRoot()
	{
		return $this->_constructor->get('root');
	}

	public function hasParent()
	{
		return true;
	}

	public function getSibling($right = true)
	{
		return null;
	}


}