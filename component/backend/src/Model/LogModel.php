<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

class LogModel extends AdminModel
{
	public function getForm($data = [], $loadData = true)
	{
		return false;
	}
}