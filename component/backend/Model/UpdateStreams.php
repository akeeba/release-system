<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model Akeeba\ReleaseSystem\Admin\Model\UpdateStreams
 *
 * Fields:
 *
 * @property  int     $id
 * @property  string  $name
 * @property  string  $alias
 * @property  string  $type
 * @property  string  $element
 * @property  int     $category
 * @property  string  $packname
 * @property  int     $client_id
 * @property  string  $folder
 * @property  int     $jedid
 * @property  string  $created
 * @property  string  $modified
 * @property  int     $checked_out
 * @property  string  $checked_out_time
 * @property  int     $published
 *
 * Filters:
 *
 * @method  $this  id()                id(int $v)
 * @method  $this  name()              name(string $v)
 * @method  $this  alias()             alias(string $v)
 * @method  $this  type()              type(string $v)
 * @method  $this  element()           element(string $v)
 * @method  $this  category()          category(int $v)
 * @method  $this  packname()          packname(string $v)
 * @method  $this  client_id()         client_id(int $v)
 * @method  $this  folder()            folder(string $v)
 * @method  $this  jedid()             jedid(int $v)
 * @method  $this  created()           created(string $v)
 * @method  $this  created_by()        created_by(int $v)
 * @method  $this  modified()          modified(string $v)
 * @method  $this  modified_by()       modified_by(int $v)
 * @method  $this  checked_out()       checked_out(int $v)
 * @method  $this  checked_out_time()  checked_out_time(string $v)
 * @method  $this  published()         published(int $v)
 *
 * Relations:
 *
 * @property  Categories  $categoryObject
 *
 */
class UpdateStreams extends DataModel
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
		$config['tableName'] = '#__ars_updatestreams';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'enabled'     => 'published',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		];
		$config['fieldsSkipChecks'] = [
			'jedid'
		];

		parent::__construct($container, $config);

		// Relations
		$this->belongsTo('categoryObject', 'Categories', 'category', 'id');

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');
	}

	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$filterOrder = $this->getState('filter_order', 'category');
		$filterOrderDir = $this->getState('filter_order_Dir', 'ASC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);
	}

	public function check()
	{
		$this->assertNotEmpty($this->name, 'ERR_USTREAM_NEEDS_NAME');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			\JLoader::import('joomla.filter.input');
			$alias = str_replace(' ', '-', strtolower($this->getFieldValue('name')));
			$this->alias = (string)preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'ERR_USTREAM_NEEDS_ALIAS');

		// Check alias for uniqueness
		$db = $this->getDBO();
		$query = $db->getQuery(true)
					->select($db->qn('alias'))
					->from($db->qn('#__ars_updatestreams'));

		if ($this->id)
		{
			$query->where('NOT(' . $db->qn('id') . '=' . $db->q($this->id) . ')');
		}

		$db->setQuery($query);
		$aliases = $db->loadColumn();

		$numericSuffix = 0;
		$alias = $this->alias;

		while (in_array($alias, $aliases) && ($numericSuffix < 100))
		{
			$alias = $this->alias . '-' . ++$numericSuffix;
		}

		$this->alias = $alias;
		$this->assertNotInArray($this->alias, $aliases, 'ERR_USTREAM_NEEDS_UNIQUE_ALIAS');

		// Automaticaly fix the type
		if (!in_array($this->type, array('components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates')))
		{
			$this->type = 'components';
		}

		// Require an element name
		$this->assertNotEmpty($this->element, 'ERR_USTREAM_NEEDS_ELEMENT');

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}

		return parent::check();
	}
}
