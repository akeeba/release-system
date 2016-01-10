<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\DataController;

class Item extends DataController
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		if ($this->getModel()->getState('limit', 0) != 0)
		{
			$this->getModel()->with([]);
		}
	}

	protected function onBeforeApplySave(&$data)
	{
		// When you deselect all items Chosen doesn't return any items in the request :(
		if (!isset($data['groups']))
		{
			$data['groups'] = array();
		}

		// Save as above *sigh*
		if (!isset($data['environments']))
		{
			$data['environments'] = array();
		}
	}

	protected function onBeforeAdd()
	{
		$this->defaultsForAdd = [
			'release_id' => 0,
			'type'       => 'file',
			'access'     => 1,
			'published'  => 0,
			'language'   => '*',
		];

		if ($stateValue = $this->getModel()->getState('release', null))
		{
			$this->defaultsForAdd['release_id'] = $stateValue;
		}

		foreach ($this->defaultsForAdd as $k => $v)
		{
			if ($stateValue = $this->getModel()->getState($k, $v))
			{
				$this->defaultsForAdd[ $k ] = $stateValue;
			}
		}
	}

}