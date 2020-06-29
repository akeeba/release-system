<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Environments;
use FOF30\View\DataView\Raw;

class Ini extends Raw
{
	use Common;

	public $items = [];

	public $envs = [];

	public $showChecksums = false;

	public $compactDisplay = false;

	protected function onBeforeIni($tpl = null): void
	{
		$this->commonSetup();

		/** @var Environments $envmodel */
		$envmodel = $this->container->factory->model('Environments')->tmpInstance();
		$rawenvs  = $envmodel->get(true);
		$envs     = [];

		if ($rawenvs->count())
		{
			foreach ($rawenvs as $env)
			{
				$envs[$env->id] = $env;
			}
		}

		$this->envs           = $envs;
		$this->showChecksums  = $this->container->params->get('show_checksums', 0) == 1;
		$this->compactDisplay = $this->container->params->get('minify_xml', 1) == 1;

		$this->setLayout('ini');

		// Set the content type to text/plain
		$this->container->platform->getDocument()->setMimeEncoding('text/plain');
	}
}
