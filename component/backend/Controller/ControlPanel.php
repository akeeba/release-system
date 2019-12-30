<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Controller\Mixin\PredefinedTaskList;
use Joomla\CMS\Language\Text;

class ControlPanel extends Controller
{
	use PredefinedTaskList;

	public function __construct(Container $container, array $config = [])
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['main'];
	}

	/**
	 * Runs before the main task, used to perform housekeeping function automatically
	 */
	protected function onBeforeMain(): bool
	{
		/** @var \Akeeba\ReleaseSystem\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model
			->checkAndFixDatabase()
			->saveMagicVariables();

		return true;
	}
}
