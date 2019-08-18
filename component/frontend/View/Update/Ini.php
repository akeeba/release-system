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

class Ini extends Raw
{
	use Common;

	public $items = array();

	protected function onBeforeIni($tpl = null): void
	{
		$this->setLayout('ini');

		// Set the content type to text/plain
		$this->container->platform->getDocument()->setMimeEncoding('text/plain');
	}
}
