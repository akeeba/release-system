<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Environments;
use FOF30\View\DataView\Raw;
use JComponentHelper;

class Xml extends Raw
{
	public $items = array();

	public $published = false;

	public $updates_name = '';
	public $updates_desc = '';
	public $category = 0;
	public $envs = [];
	public $showChecksums = false;

	public function display($tpl = null)
	{
		$task     = $this->getModel()->getState('task', 'all');

		if (!in_array($task, ['all', 'category', 'stream', 'jed']))
		{
			$this->doTask = 'all';
		}

		$this->container->platform->getDocument()->setMimeEncoding('text/xml');

		return parent::display($tpl);
	}

	protected function onBeforeAll()
	{
		$params             = JComponentHelper::getParams('com_ars');

		$this->updates_name = $params->get('updates_name', '');
		$this->updates_desc = $params->get('updates_desc', '');

		$this->setLayout('all');
	}

	protected function onBeforeCategory()
	{
		$category       = $this->input->getCmd('id', '');
		$this->category = $category;

		$this->setLayout('category');
	}

	protected function onBeforeStream()
	{
		/** @var Environments $envmodel */
		$envmodel = $this->container->factory->model('Environments')->tmpInstance();
		$rawenvs  = $envmodel->get(true);
		$envs     = array();

		if ($rawenvs->count())
		{
			foreach ($rawenvs as $env)
			{
				$envs[$env->id] = $env;
			}
		}

		$this->envs          = $envs;
		$this->showChecksums = $this->container->params->get('show_checksums', 0) == 1;
		$this->setLayout('stream');
	}

	protected function onBeforeJed()
	{
		/** @var Environments $envmodel */
		$envmodel = $this->container->factory->model('Environments')->tmpInstance();
		$rawenvs  = $envmodel->get(true);
		$envs     = array();

		if (!empty($rawenvs))
		{
			foreach ($rawenvs as $env)
			{
				$envs[ $env->id ] = $env;
			}
		}

		$this->envs  = $envs;
		$this->setLayout('jed');
	}
}
