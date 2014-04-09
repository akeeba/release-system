<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableRelease extends F0FTable
{
	/**
	 * Instantiate the table object
	 *
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct( $table, $key, &$db )
	{
		parent::__construct( '#__ars_releases', 'id', $db );

		$this->_columnAlias = array(
			'enabled'		=> 'published',
			'slug'			=> 'alias',
			'created_on'	=> 'created',
			'modified_on'	=> 'modified',
			'locked_on'		=> 'checked_out_time',
			'locked_by'		=> 'checked_out',
		);

		$this->access = 1;
		$this->maturity = 'alpha';
		$this->language = '*';
	}

	/**
	 * Checks the record for validity
	 *
	 * @return int True if the record is valid
	 */
	function check()
	{
		// If the category is missing, throw an error
		if(!$this->category_id) {
			$this->setError(JText::_('COM_RELEASE_ERR_NEEDS_CATEGORY'));
			return false;
		}

		// Get some useful info
		$db = $this->getDBO();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('version'),
				$db->qn('alias')
			))->from($db->qn('#__ars_releases'))
			->where($db->qn('category_id').' = '.$db->q($this->category_id));
		if($this->id) {
			$query->where('NOT('.$db->qn('id').'='.$db->q($this->id).')');
		}
		$db->setQuery($query);
		$info = $db->loadAssocList();
		$versions = array(); $aliases = array();
		foreach($info as $infoitem)
		{
			$versions[] = $infoitem['version'];
			$aliases[] = $infoitem['alias'];
		}

		if(!$this->version) {
			$this->setError(JText::_('COM_RELEASE_ERR_NEEDS_VERSION'));
			return false;
		}

		if(in_array($this->version, $versions)) {
			$this->setError(JText::_('COM_RELEASE_ERR_NEEDS_VERSION_UNIQUE'));
			return false;
		}

		// If the alias is missing, auto-create a new one
		if(!$this->alias) {
			JLoader::import('joomla.filter.input');

			// Get the category title
			if(!class_exists('ArsModelCategories')) {
				require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/categories.php';
			}
			$catModel = new ArsModelCategories();
			$catModel->setId($this->category_id);
			$catItem = $catModel->getItem();

			// Create a smart alias
			$alias = strtolower($catItem->alias.'-'.$this->version);
			$alias = str_replace(' ', '-', $alias);
			$alias = str_replace('.', '-', $alias);
			$this->alias = (string) preg_replace( '/[^A-Z0-9_-]/i', '', $alias );
		}

		if(!$this->alias) {
			$this->setError(JText::_('COM_RELEASE_ERR_NEEDS_ALIAS'));
			return false;
		}

		if(in_array($this->alias, $aliases)) {
			$this->setError(JText::_('COM_RELEASE_ERR_NEEDS_ALIAS_UNIQUE'));
			return false;
		}

		// Automaticaly fix the maturity
		if(!in_array($this->maturity, array('alpha','beta','rc','stable')))
		{
			$this->maturity = 'beta';
		}

		JLoader::import('joomla.filter.filterinput');
		$filter = JFilterInput::getInstance(null, null, 1, 1);

		// Filter the description using a safe HTML filter
		if(!empty($this->description))
		{
			$this->description = $filter->clean($this->description);
		}

		// Filter the notes using a safe HTML filter
		if(!empty($this->notes))
		{
			$this->notes = $filter->clean($this->notes);
		}

		// Fix the groups
		if(is_array($this->groups)) $this->groups = implode(',', $this->groups);
		// Set the access to registered if there are subscriptions defined
		if(!empty($this->groups) && ($this->access == 1))
		{
			$this->access = 2;
		}

		JLoader::import('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if(!$this->created_by && empty($this->id))
		{
			$this->created_by = $user->id;
			if(empty($this->created)) $this->created = $date->toSql();
		}
		else
		{
			$this->modified_by = $user->id;
			$this->modified = $date->toSql();
		}

		/*
		if(empty($this->ordering)) {
			$this->ordering = $this->getNextOrder();
		}
		*/

		if(empty($this->published) && ($this->published !== 0) )
		{
			$this->published = 0;
		}

		return true;
	}

    protected function onBeforeStore($updateNulls)
    {
        // I'm going to save a new record, let's shift all old record by 1 and put this as the first one
        if(!$this->id)
        {
            $this->ordering = 1;

            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                        ->update($db->qn('#__ars_releases'))
                        ->set($db->qn('ordering').' = '.$db->qn('ordering').' + '.$db->q(1));
            $db->setQuery($query)->execute();
        }

        return parent::onBeforeStore($updateNulls);
    }
}