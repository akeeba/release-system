<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Controller\DataController;

class Release extends DataController
{
	protected function onBeforeApplySave(&$data)
	{
		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}
	}

	protected function onBeforeAdd()
	{
		$this->defaultsForAdd = [
			'category_id' => 0,
			'maturity'    => 'stable',
			'access'      => 1,
			'published'   => 0,
			'language'    => '*',
		];

		foreach ($this->defaultsForAdd as $k => $v)
		{
			if ($stateValue = $this->getModel()->getState($k, $v))
			{
				$this->defaultsForAdd[$k] = $stateValue;
			}
		}
	}
}