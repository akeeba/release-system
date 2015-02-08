<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsTableUpdatestream extends F0FTable
{
	/**
	 * Instantiate the table object
	 *
	 * @param JDatabase $db The Joomla! database object
	 */
	function __construct($table, $key, &$db)
	{
		parent::__construct('#__ars_updatestreams', 'id', $db);

		$this->_columnAlias = array(
			'enabled'     => 'published',
			'slug'        => 'alias',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		);

		$this->type = 'components';
	}

	/**
	 * Checks the record for validity
	 *
	 * @return int True if the record is valid
	 */
	function check()
	{
		// If the title is missing, throw an error
		if (!$this->name)
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_NAME'));

			return false;
		}

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			JLoader::import('joomla.filter.input');
			$alias = str_replace(' ', '-', strtolower($this->name));
			$this->alias = (string)preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		// If no alias could be auto-generated, fail
		if (!$this->alias)
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_ALIAS'));

			return false;
		}

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
		if (in_array($this->alias, $aliases))
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_UNIQUE_ALIAS'));

			return false;
		}

		// Automaticaly fix the type
		if (!in_array($this->type, array('components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates')))
		{
			$this->type = 'components';
		}

		// Require an element name
		if (empty($this->element))
		{
			$this->setError(JText::_('ERR_USTREAM_NEEDS_ELEMENT'));

			return false;
		}

		JLoader::import('joomla.utilities.date');
		$user = JFactory::getUser();
		$date = new JDate();
		if (!$this->created_by && empty($this->id))
		{
			$this->created_by = $user->id;
			$this->created = $date->toSql();
		}
		else
		{
			$this->modified_by = $user->id;
			$this->modified = $date->toSql();
		}

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}

		return true;
	}
}