<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF40\Container\Container;
use FOF40\Date\Date;
use FOF40\Model\DataModel;
use FOF40\Model\Mixin\Assertions;
use FOF40\IP\IPHelper as Ip;
use JDatabaseQuery;

/**
 * @property int           $id
 * @property int           $user_id
 * @property int           $item_id
 * @property string        $accessed_on
 * @property string        $referer
 * @property string        $ip
 * @property int           $authorized
 *
 * @property-read Items	$item
 */
class Logs extends DataModel
{
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
		$config['tableName'] = '#__ars_log';
		$config['idFieldName'] = 'id';

		parent::__construct($container, $config);

		// Disable automatic checks
		$this->autoChecks = false;

		// Relations
		$this->hasOne('item', 'Items', 'item_id', 'id');

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('RelationFilters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');

		$this->with(['item']);
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

		$fltItemText = $this->getState('itemtext', null, 'string');
		$fltUserText = $this->getState('usertext', null, 'string');
		$fltUserId   = $this->getState('user_id', null, 'int');
		$fltCategory = $this->getState('category', null, 'int');
		$fltVersion  = $this->getState('version', null, 'int');

		if ($fltItemText)
		{
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$itemIDs = $this->getItems($fltItemText);

			if (empty($itemIDs))
			{
				$query->where('FALSE');
			}
			else
			{
				$itemIDs = array_map(array($db, 'quote'), $itemIDs);
				$ids = implode(',', $itemIDs);
				$query->where($db->qn('item_id') . ' IN(' . $ids . ')');
			}
		}

		if ($fltUserId)
		{
			if (is_array($fltUserId))
			{
				$query->where($db->qn('user_id') . ' IN(' . implode(',', array_map([$db, 'q'], $fltUserId)) . ')');
			}
			else
			{
				$query->where($db->qn('user_id') . ' = ' . $db->q($fltUserId));
			}
		}
		elseif ($fltUserText)
		{
			// This extra query approach is required for performance on very large log tables (multiple millions of rows)
			$userIDs = $this->getUsers($fltUserText);

			if (empty($userIDs))
			{
				$query->where('FALSE');
			}
			else
			{
				$userIDs = array_map(array($db, 'quote'), $userIDs);
				$ids = implode(',', $userIDs);
				$query->where($db->qn('user_id') . ' IN(' . $ids . ')');
			}
		}

		if ($fltCategory)
		{
			// We use the double nested subquery instead of whereHas() for memory conservation reasons at the expense of
			// performance.
			$query_inner = $db->getQuery(true)
							  ->select($db->qn('id'))
							  ->from($db->qn('#__ars_releases'))
							  ->where($db->qn('category_id') . ' = ' . $db->q($fltCategory));
			$query_outer = $db->getQuery(true)
							  ->select($db->qn('id'))
							  ->from($db->qn('#__ars_items'))
							  ->where($db->qn('release_id') . ' IN (' . $query_inner . ')');
			$db->setQuery($query_outer);
			$ids    = $db->loadColumn();
			// Add an always false clause so we won't have SQL errors in case of an empty set
			$ids[]  = '-1';
			$clause = '(' . implode(", ", $ids) . ')';

			$query->where($db->qn('item_id') . ' IN ' . $clause);
		}

		if ($fltVersion)
		{
			// We use the nested subquery instead of whereHas() for memory conservation reasons at the expense of
			// performance.
			$query_outer = $db->getQuery(true)
							  ->select($db->qn('id'))
							  ->from($db->qn('#__ars_items'))
							  ->where($db->qn('release_id') . ' = ' . $db->q($fltVersion));
			$db->setQuery($query_outer);
			$ids    = $db->loadColumn();
			// Add an always false clause so we won't have SQL errors in case of an empty set
			$ids[] = '-1';
			$clause = '(' . implode(", ", $ids) . ')';

			$query->where($db->qn('item_id') . ' IN ' . $clause);
		}

		$filterOrder = $this->getState('filter_order', 'accessed_on');
		$filterOrderDir = $this->getState('filter_order_Dir', 'DESC');
		$this->setState('filter_order', $filterOrder);
		$this->setState('filter_order_Dir', $filterOrderDir);
	}

	public function check(): self
	{
		if (empty($this->user_id))
		{
			$user          = $this->container->platform->getUser();
			$this->user_id = $user->id;
		}

		if (empty($this->item_id))
		{
			// Yeah, I know, the Model shouldn't access the input directly but this saves us a lot of code in the
			// front-end models where we're logging downloads.
			$this->item_id = $this->input->getInt('id', 0);
		}

		if (empty($this->accessed_on) || ($this->accessed_on == '0000-00-00 00:00:00') || ($this->accessed_on == $this->container->db->getNullDate()))
		{
			$date              = new Date();
			$this->accessed_on = $date->toSql();
		}

		if (empty($this->referer))
		{
			if (isset($_SERVER['HTTP_REFERER']))
			{
				$this->referer = $_SERVER['HTTP_REFERER'];
			}
		}

		if (empty($this->ip))
		{
			$this->ip = Ip::getIp();
		}

		return parent::check();
	}


	/**
	 * Returns the user IDs whose username, email address or real name contains the $frag string
	 *
	 * @param string $frag
	 *
	 * @return array|null
	 */
	private function getUsers(string $frag): ?array
	{
		$db = $this->getDBO();

		$qfrag = $db->q("%" . $frag . "%");
		$query = $db->getQuery(true)
					->select($db->qn('id'))
					->from($db->qn('#__users'))
					->where($db->qn('name') . ' LIKE ' . $qfrag, 'OR')
					->where($db->qn('username') . ' LIKE ' . $qfrag, 'OR')
					->where($db->qn('email') . ' LIKE ' . $qfrag, 'OR')
					->where($db->qn('params') . ' LIKE ' . $qfrag, 'OR');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Gets a list of download item IDs whose title contains the $frag string
	 *
	 * @param string $frag
	 *
	 * @return array|null
	 */
	private function getItems(string $frag): ?array
	{
		$db    = $this->getDBO();
		$qfrag = $db->q("%" . $frag . "%");
		$query = $db->getQuery(true)
					->select($db->qn('id'))
					->from($db->qn('#__ars_items'))
					->where($db->qn('title') . ' LIKE ' . $qfrag);

		$db->setQuery($query);

		return $db->loadColumn();
	}
}
