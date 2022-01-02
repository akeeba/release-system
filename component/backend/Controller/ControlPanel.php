<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Updates;
use Exception;
use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Controller\Mixin\PredefinedTaskList;
use FOF40\Utils\ViewManifestMigration;
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

		/** @var Updates $updatesModel */
		$updatesModel = $this->getModel('Updates');
		$updatesModel->refreshUpdateSite();

		ViewManifestMigration::migrateJoomla4MenuXMLFiles($this->container);
		ViewManifestMigration::removeJoomla3LegacyViews($this->container);

		return true;
	}
}
