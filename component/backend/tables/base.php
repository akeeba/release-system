<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.database.table');

class ArsTable extends JTable
{
	/**
	 * Generic check for whether dependancies exist for this object in the db schema
	 */
	function canDelete( $oid=null, $joins=null )
	{
		$k = $this->_tbl_key;
		if ($oid) {
			$this->$k = intval( $oid );
		}

		if (is_array( $joins ))
		{
			$select = "`master`.$k";
			$join = "";
			foreach( $joins as $table )
			{
				$select .= ', COUNT(DISTINCT `'.$table['name'].'`.'.$table['idfield'].') AS '.$table['idalias'];
				$join .= ' LEFT JOIN '.$table['name'].' ON '.$table['joinfield'].' = `master`.'.$k;
			}

			$query = 'SELECT '. $select
			. ' FROM '. $this->_tbl.' AS `master` '
			. $join
			. ' WHERE `master`.'. $k .' = '. $this->_db->Quote($this->$k)
			. ' GROUP BY `master`.'. $k
			;
			$this->_db->setQuery( $query );

			if (!$obj = $this->_db->loadObject())
			{
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
			$msg = array();
			$i = 0;
			foreach( $joins as $table )
			{
				$k = $table['idfield'] . $i;
				if ($obj->$k)
				{
					$msg[] = JText::_( $table['label'] );
				}
				$i++;
			}

			if (count( $msg ))
			{
				$this->setError("noDeleteRecord" . ": " . implode( ', ', $msg ));
				return false;
			}
			else
			{
				return true;
			}
		}

		return true;
	}
}