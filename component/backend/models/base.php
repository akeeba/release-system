<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.model');

abstract class ArsModelBase extends JModel
{
	protected $table = null;
	protected $otable = null;

	protected $id_list = array();
	protected $id = null;
	protected $record = null;
	protected $list = null;

	protected $pagination = null;
	protected $total = 0;

	public function __construct($config = array())
	{
		parent::__construct($config);

		// Assign the correct table
		if(array_key_exists('table',$config)) {
			$this->table = $config['table'];
		} else {
			$name = $this->getName();
			$this->table = str_replace('Model', '', $name);
		}


		// Get and store the pagination request variables
		$app =& JFactory::getApplication();
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$limitstart = $app->getUserStateFromRequest(JRequest::getCmd('option','com_ars').$this->getName().'limitstart','limitstart',0);
		$this->setState('limit',$limit);
		$this->setState('limitstart',$limitstart);

		// Get the ID or list of IDs from the request or the configuration
		if(array_key_exists('cid', $config)) {
			$cid = $config['cid'];
		} else {
			$cid = JRequest::getVar('cid', null, 'DEFAULT', 'array');
		}
		if(array_key_exists('id', $config)) {
			$id = $config['id'];
		} else {
			$id = JRequest::getInt('id', 0);
		}

		if(is_array($cid) && !empty($cid))
		{
			$this->setIds($cid);
		}
		else
		{
			$this->setId($id);
		}
	}

	/**
	 * Sets the list of IDs from the request data
	 */
	public function setIDsFromRequest()
	{
		// Get the ID or list of IDs from the request or the configuration
		$cid = JRequest::getVar('cid', null, 'DEFAULT', 'array');
		$id = JRequest::getInt('id', 0);

		if(is_array($cid) && !empty($cid))
		{
			$this->setIds($cid);
		}
		else
		{
			$this->setId($id);
		}
	}

	/**
	 * Sets the ID and resets internal data
	 * @param int $id The ID to use
	 */
	public final function setId($id=0)
	{
		$this->reset();
		$this->id = (int)$id;
		$this->id_list = array($this->id);
	}

	/**
	 * Returns the currently set ID
	 * @return int
	 */
	public final function getId()
	{
		return $this->id;
	}

	/**
	 * Sets a list of IDs for batch operations from an array and resets the model
	 */
	public final function setIds($idlist)
	{
		$this->reset();
		$this->id_list = array();
		$this->id = 0;
		if(is_array($idlist) && !empty($idlist)) {
			foreach($idlist as $value)
			{
				$this->id_list[] = (int)$value;
			}
			$this->id = $this->id_list[0];
		}
	}

	/**
	 * Returns the list of IDs for batch operations
	 * @return array An array of integers
	 */
	public final function getIds()
	{
		return $this->id_list;
	}

	/**
	 * Resets the model, like it was freshly loaded
	 */
	public function reset()
	{
		$this->id = 0;
		$this->id_list = null;
		$this->record = null;
		$this->list = null;
		$this->pagination = null;
		$this->total = 0;
		$this->otable = null;
	}

	/**
	 * Returns a single item. It uses the id set with setId, or the first ID in
	 * the list of IDs for batch operations
	 * @return JTable A copy of the item's JTable array
	 */
	public final function &getItem()
	{
		if(empty($this->record))
		{
			$table = $this->getTable($this->table);
			$table->load($this->id);
			$this->record = $table;

			// Do we have saved data?
			$session = JFactory::getSession();
			$serialized = $session->get($this->getHash().'savedata', null);
			if(!empty($serialized))
			{
				$data = @unserialize($serialized);
				if($data !== false)
				{
					$this->record->bind($data);
				}
			}

		}

		return $this->record;
	}

	/**
	 * Returns a list of items
	 * @param bool $overrideLimits When true, the limits (pagination) will be ignored
	 * @return array
	 */
	public final function &getItemList($overrideLimits = false)
	{
		if(empty($this->list)) {
			$table = $this->getTable($this->table);
			$source = $table->getTableName();

			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			$query = $this->buildQuery($overrideLimits);
			if(!$overrideLimits)
				$this->list = $this->_getList($query, $limitstart, $limit);
			else
				$this->list = $this->_getList($query);
		}

		return $this->list;
	}

	/**
	 * Binds the data to the model and tries to save it
	 * @param array|object $data The source data array or object
	 * @return bool True on success
	 */
	public function save($data)
	{
		$this->otable = null;

		$table = $this->getTable($this->table);

		$key = $table->getKeyName();
		if(array_key_exists($key, (array)$data))
		{
			$aData = (array)$data;
			$oid = $aData[$key];
			$table->load($oid);
		}

		if(!$table->save($data))
		{
			$this->setError($table->getError());
			return false;
		}

		$this->otable = $table;
		return true;
	}

	/**
	 * Returns the table object after the last save() operation
	 * @return JTable
	 */
	public final function getSavedTable()
	{
		return $this->otable;
	}

	/**
	 * Deletes one or several items
	 * @return bool
	 */
	public function delete()
	{
		if(is_array($this->id_list) && !empty($this->id_list)) {
			$table = $this->getTable($this->table);
			foreach($this->id_list as $id) {
				if(!$table->delete($id)) {
					$this->setError($table->getError());
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Toggles the published state of one or several items
	 * @param int $publish The publishing state to set (e.g. 0 is unpublished)
	 * @param int $user The user ID performing this action
	 * @return bool
	 */
	public function publish($publish = 1, $user = null)
	{
		if(is_array($this->id_list) && !empty($this->id_list)) {
			if(empty($user)) {
				$oUser = JFactory::getUser();
				$user = $oUser->id;
			}
			$table = $this->getTable($this->table);
			if(!$table->publish($this->id_list, $publish, $user) ) {
				$this->setError($table->getError());
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks out the current item
	 * @return bool
	 */
	public function checkout()
	{
		$table = $this->getTable($this->table);
		$status = $table->checkout(JFactory::getUser()->id, $this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Checks in the current item
	 * @return bool
	 */
	public function checkin(){
		$table = $this->getTable($this->table);
		$status = $table->checkin($this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Tells you if the current item is checked out or not
	 * @return bool
	 */
	public function isCheckedOut() {
		$table = $this->getTable($this->table);
		$status = $table->isCheckedOut($this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Increments the hit counter
	 * @return bool
	 */
	public function hit() {
		$table = $this->getTable($this->table);
		$status = $table->hit($this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Moves the current item up or down in the ordering list
	 * @param <type> $dirn
	 * @return bool
	 */
	public function move( $dirn ) {
		$table = $this->getTable($this->table);

		$id = $this->getId();
		$status = $table->load($id);
		if(!$status) $this->setError($table->getError());
		if(!$status) return false;

		$status = $table->move($dirn);
		if(!$status) $this->setError($table->getError());

		return $status;
	}

	/**
	 * Reorders all items in the table
	 * @return bool
	 */
	public function reorder()
	{
		$table = $this->getTable($this->table);
		$status = $table->reorder( $this->getReorderWhere() );
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 *
	 */
	public final function getPagination()
	{
		if( empty($this->pagination) )
		{
			// Import the pagination library
			jimport('joomla.html.pagination');

			// Prepare pagination values
			$total = $this->getTotal();
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			// Create the pagination object
			$this->pagination = new JPagination($total, $limitstart, $limit);
		}

		return $this->pagination;
	}

	/**
	 * Get the number of all items
	 *
	 * @access public
	 * @return integer
	 */
	public final function getTotal()
	{
		if( empty($this->total) )
		{
			$query = $this->buildCountQuery();
			if($query === false) {
				$query = $this->buildQuery(false);
				$query = "SELECT COUNT(*) FROM ($query) AS a";
			}

			$this->_db->setQuery( $query );
			$this->_db->query();
			
			$this->total = $this->_db->loadResult();
		}

		return $this->total;
	}

	/**
	 * Get a filtered state variable
	 * @param string $key
	 * @param mixed $default
	 * @param string $filter_type
	 * @return mixed
	 */
	public function getState($key = null, $default = null, $filter_type = 'raw')
	{
		if(empty($key)) {
			return parent::getState();
		}

		$value = parent::getState($key);
		if(is_null($value))
		{
			// Try to fetch it from the request or session
			$app = JFactory::getApplication();
			$value = $app->getUserStateFromRequest($this->getHash().$key,$key,null);
			//$value = JRequest::getVar($key, null);

			if(is_null($value))	return $default;
		}
		
		if( strtoupper($filter_type) == 'RAW' )
		{
			return $value;
		}
		else
		{
			jimport('joomla.filter.filterinput');
			$filter = new JFilterInput();
			return $filter->clean($value, $filter_type);
		}
	}

	public final function getHash()
	{
		return JRequest::getCmd('option').'.'.str_replace('Model', '', $this->getName()).'.';
	}

	public function getReorderWhere()
	{
		return '';
	}

	/**
	 * Builds the SELECT query
	 */
	abstract public function buildQuery($overrideLimits = false);

	public function buildCountQuery()
	{
		return false;
	}
}