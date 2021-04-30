<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF40\Container\Container;
use FOF40\Model\DataModel;
use FOF40\Model\Mixin\Assertions;
use FOF40\Model\Mixin\ImplodedArrays;
use JDatabaseQuery;

class Environments extends DataModel
{
	use ImplodedArrays;
	use Assertions;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF40\Model\DataModel\Exception\NoTableColumns
	 * @see DataModel::__construct()
	 *
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__ars_environments';
		$config['idFieldName'] = 'id';

		parent::__construct($container, $config);

		$this->blacklistFilters([
			'title'
		]);

		// Behaviours
		$this->addBehaviour('Filters');
	}

	/**
	 * Implements custom filtering
	 *
	 * @param JDatabaseQuery $query          The model query we're operating on
	 * @param   bool         $overrideLimits Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(JDatabaseQuery &$query, bool $overrideLimits = false): void
	{
		$db = $this->getDbo();

		$fltSearch = $this->getState('search', null, 'string');

		if ($fltSearch)
		{
			$fltSearch = "%$fltSearch%";

			$query->where($db->qn('title') . ' LIKE ' . $db->q($fltSearch));
		}

		$filterOrder = $this->getState('filter_order', 'title');
		$filterOrderDir = $this->getState('filter_order_Dir', 'ASC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);
	}

}
