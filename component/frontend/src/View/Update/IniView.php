<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TaskBasedEvents;
use Akeeba\Component\ARS\Site\Model\EnvironmentsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class IniView extends HtmlView
{
	use Common;
	use TaskBasedEvents;

	public $items = [];

	public $envs = [];

	public $showChecksums = false;

	public $compactDisplay = false;

	protected function onBeforeIni($tpl = null): void
	{
		$this->commonSetup();

		/** @var EnvironmentsModel $envModel */
		$envModel = $this->getModel('Environments');
		$params   = Factory::getApplication()->getParams('com_ars');

		$this->envs           = $envModel->getEnvironmentXMLTitles();
		$this->showChecksums  = $params->get('show_checksums', 0) == 1;
		$this->compactDisplay = $params->get('minify_xml', 1) == 1;

		$this->setLayout('ini');

		// Set the content type to text/plain
		$this->document->setMimeEncoding('text/plain');
	}

}