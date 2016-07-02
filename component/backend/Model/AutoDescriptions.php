<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Mixin;
use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for automatic item descriptions
 *
 * Fields:
 *
 * @property  int     $id
 * @property  int     $category
 * @property  string  $packname
 * @property  string  $title
 * @property  string  $description
 * @property  string  $environments
 * @property  int     $published
 *
 * Filters:
 *
 * @method  $this  id()            id(int $v)
 * @method  $this  category()      category(int $v)
 * @method  $this  packname()      packname(string $v)
 * @method  $this  title()         title(string $v)
 * @method  $this  description()   description(string $v)
 * @method  $this  environments()  environments(string $v)
 * @method  $this  published()     published(int $v)
 *
 * Relations:
 *
 * @property  Categories  $categoryObject
 */
class AutoDescriptions extends DataModel
{
	use Mixin\ImplodedArrays;

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
		$config['tableName'] = '#__ars_autoitemdesc';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'enabled' => 'published',
		];

		parent::__construct($container, $config);

		// Behaviours
		$this->addBehaviour('Filters');

		// Relations
		$this->belongsTo('categoryObject', 'Categories', 'category', 'id');

		// Eager loaded relations setup
		$this->with(['categoryObject']);
	}

	public function check()
	{
		if (empty($this->published))
		{
			$this->published = 0;
		}

		return parent::check();
	}

	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$filterOrder = $this->getState('filter_order', 'category');
		$filterOrderDir = $this->getState('filter_order_Dir', 'ASC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);
	}

	/**
	 * Converts the loaded comma-separated list of environments into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getEnvironmentsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of environments into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setEnvironmentsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}

}