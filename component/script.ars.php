<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @noinspection PhpUnused */

defined('_JEXEC') || die;

use Akeeba\Component\Ars\Administrator\Model\UpgradeModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;

/**
 * Akeeba Release System package extension installation script file.
 *
 * @see https://docs.joomla.org/Manifest_files#Script_file
 * @see UpgradeModel
 */
class Pkg_ArsInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	public function __construct()
	{
		$this->minimumJoomla = '4.2.0';
		$this->minimumPhp    = '7.4.0';
	}

	/**
	 * Called after any type of installation / uninstallation action.
	 *
	 * @param   string          $type    Which action is happening (install|uninstall|discover_install|update)
	 * @param   PackageAdapter  $parent  The object responsible for running this script
	 *
	 * @return  bool
	 * @since   9.0.0
	 */
	public function postflight(string $type, PackageAdapter $parent): bool
	{
		// Do not run on uninstall.
		if ($type === 'uninstall')
		{
			return true;
		}

		// Install the dashboard module if necessary
		$this->conditionalInstallDashboard('com_ars-ars', 'ars');

		// Run the post-upgrade code
		$model = $this->getUpgradeModel();

		if (empty($model))
		{
			return true;
		}

		return $model->postflight($type, $parent);
	}

	private function conditionalInstallDashboard(string $dashboard, string $preset): void
	{
		$position = 'cpanel-' . $dashboard;

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
		            ->select('COUNT(*)')
		            ->from($db->quoteName('#__modules'))
		            ->where([
			            $db->quoteName('module') . ' = ' . $db->quote('mod_submenu'),
			            $db->quoteName('client_id') . ' = ' . $db->quote(1),
			            $db->quoteName('position') . ' = :position',
		            ])
		            ->bind(':position', $position);

		$modules = $db->setQuery($query)->loadResult() ?: 0;

		if ($modules == 0)
		{
			$this->addDashboardMenu($dashboard, $preset);
		}
	}

	/**
	 * Get the UpgradeModel of the installed component
	 *
	 * @return  UpgradeModel|null  The upgrade Model. NULL if it cannot be loaded.
	 * @since   9.0.0
	 */
	private function getUpgradeModel(): ?UpgradeModel
	{
		// Make sure the latest version of the Model file will be loaded, regardless of the OPcache state.
		$filePath = JPATH_ADMINISTRATOR . '/components/com_ars/src/Model/UpgradeModel.php';

		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($filePath = JPATH_ADMINISTRATOR . '/components/com_ars/src/Model/UpgradeModel.php');
		}

		// Can I please load the model?
		if (!class_exists('\Akeeba\Component\Ars\Administrator\Model\UpgradeModel'))
		{
			if (!file_exists($filePath) || !is_readable($filePath))
			{
				return null;
			}

			/** @noinspection PhpIncludeInspection */
			include_once $filePath;
		}

		if (!class_exists('\Akeeba\Component\Ars\Administrator\Model\UpgradeModel'))
		{
			return null;
		}

		try
		{
			return new UpgradeModel();
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}
