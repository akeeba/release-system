<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die();

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for Visual Groups
 *
 * Fields:
 *
 * @property  int     $id
 * @property  string  $title
 * @property  string  $description
 * @property  string  $created
 * @property  string  $modified
 * @property  int     $checked_out
 * @property  string  $checked_out_time
 * @property  int     $published
 *
 * Filters:
 *
 * @method  $this  id()                id(int $v)
 * @method  $this  title()             title(string $v)
 * @method  $this  description()       description(string $v)
 * @method  $this  created()           created(string $v)
 * @method  $this  created_by()        created_by(int $v)
 * @method  $this  modified()          modified(string $v)
 * @method  $this  modified_by()       modified_by(int $v)
 * @method  $this  checked_out()       checked_out(int $v)
 * @method  $this  checked_out_time()  checked_out_time(string $v)
 * @method  $this  ordering()          ordering(int $v)
 * @method  $this  published()         published(int $v)
 *
 * Relations:
 *
 * @property  Categories  $categories
 *
 */
class VisualGroups extends DataModel
{
	use Mixin\Assertions;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @see DataModel::__construct()
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF30\Model\DataModel\Exception\NoTableColumns
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__ars_vgroups';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'enabled'     => 'published',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		];

		parent::__construct($container, $config);

		// Relations
		$this->hasMany('categories', 'Categories', 'id', 'vgroup_id');

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');
	}

	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$filterOrder = $this->getState('filter_order', 'ordering');
		$filterOrderDir = $this->getState('filter_order_Dir', 'ASC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);
	}

	/**
	 * Triggered after building the query. If we're in the front-end we force the sorting to be always by ordering,
	 * ascending
	 *
	 * @param   \JDatabaseQuery  $query
	 * @param   bool             $overrideLimits
	 */
	protected function onAfterBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		if ($this->container->platform->isFrontend())
		{
			$query->order($this->getDbo()->qn('ordering') . ' ASC');
		}
	}

	public function check()
	{
		$this->assertNotEmpty($this->title, 'ERR_VGROUP_NEEDS_TITLE');

		if (empty($this->ordering))
		{
			$this->ordering = $this->getNextOrder();
		}

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}

		return parent::check();
	}

	/**
	 * Gets the next available ordering number
	 *
	 * @return  int
	 */
	protected function getNextOrder()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('MAX(' . $db->qn('ordering') . ')')
			->from($this->tableName);

		$maxOrder = $db->setQuery($query)->loadResult();

		if (empty($maxOrder))
		{
			return 1;
		}

		return (int) $maxOrder + 1;
	}

	/**
	 * Checks if we are allowed to delete this record. If there are items linked to this release then the deletion
	 * will fails with a RuntimeException.
	 *
	 * @param   int  $oid  The numeric ID of the category to delete
	 *
	 * @return  void
	 */
	function onBeforeDelete(&$oid)
	{
		$joins = array(
			array(
				'label'     => 'categories',
				'name'      => '#__ars_categories',
				'idfield'   => 'id',
				'idalias'   => 'vgroup_id',
				'joinfield' => 'vgroup_id'
			)
		);

		$this->canDelete($oid, $joins);
	}
}