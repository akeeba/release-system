<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

trait TableColumnAliasTrait
{
	/**
	 * Magic setter, is aware of column aliases.
	 *
	 * This is required for using Joomla's batch processing to copy / move records of tables which do not have a catid
	 * column.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		if ($this->hasField($name))
		{
			$realColumn        = $this->getColumnAlias($name);
			$this->$realColumn = $value;

			return;
		}

		$this->$name = $value;
	}

}