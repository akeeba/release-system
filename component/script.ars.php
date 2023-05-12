<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @noinspection PhpUnused */

defined('_JEXEC') || die;

use Akeeba\Component\Ars\Administrator\Model\UpgradeModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Akeeba Release System package extension installation script file.
 *
 * @see https://docs.joomla.org/Manifest_files#Script_file
 * @see UpgradeModel
 */
class Pkg_ArsInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	/**
	 * @since 7.1.0
	 * @var   DatabaseDriver|DatabaseInterface|null
	 */
	protected $dbo;

	public function __construct()
	{
		$this->minimumJoomla = '4.2.0';
		$this->minimumPhp    = '8.0.0';
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
		$this->setDboFromAdapter($parent);

		// Do not run on uninstall.
		if ($type === 'uninstall')
		{
			return true;
		}

		// Remove obsolete update site
		$this->removeOldUpdateSites();

		// Install the dashboard modules if necessary
		if (!$this->isModuleInDashboard('com-ars-ars', 'mod_submenu'))
		{
			$this->addDashboardMenu('com-ars-ars', 'ars');
		}

		if (!$this->isModuleInDashboard('com-ars-ars', 'mod_arsgraph'))
		{
			$this->addDashboardModule('com-ars-ars', 'mod_arsgraph');
		}

		// Run the post-upgrade code
		$model = $this->getUpgradeModel();

		if (empty($model))
		{
			return true;
		}

		return $model->postflight($type, $parent);
	}

	/**
	 * Creates modules in the Dashboard
	 *
	 * @param   string  $dashboard  The name of the dashboard
	 * @param   string  $module     The name of the admin module to check if it exists
	 * @param   array   $params     The list of parameters to set to the module
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   7.1.0
	 */
	private function addDashboardModule(string $dashboard, string $module, array $params = [])
	{
		$model  = Factory::getApplication()->bootComponent('com_modules')->getMVCFactory()->createModel('Module', 'Administrator', ['ignore_request' => true]);
		$module = [
			'id'         => 0,
			'asset_id'   => 0,
			'language'   => '*',
			'note'       => '',
			'published'  => 1,
			'assignment' => 0,
			'client_id'  => 1,
			'showtitle'  => 0,
			'content'    => '',
			'module'     => $module,
			'position'   => 'cpanel-' . $dashboard,
		];

		// Try to get a translated module title, otherwise fall back to a fixed string.
		$titleKey        = strtoupper('COM_' . $this->extension . '_DASHBOARD_' . $dashboard . '_TITLE');
		$title           = Text::_($titleKey);
		$module['title'] = ($title === $titleKey) ? ucfirst($dashboard) . ' Dashboard Module' : $title;

		$module['access'] = (int) Factory::getApplication()->get('access', 1);
		$module['params'] = array_merge([
			'menutype' => '*',
			'style'    => 'System-none',
		], $params);

		if (!$model->save($module))
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_FAILED_TO_CREATE_DASHBOARD', $model->getError()));
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
			opcache_invalidate($filePath = JPATH_ADMINISTRATOR . '/components/com_ars/src/Model/UpgradeModel.php', true);
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
			$upgradeModel = new UpgradeModel();
		}
		catch (Throwable $e)
		{
			return null;
		}

		if (method_exists($upgradeModel, 'setDatabase'))
		{
			$upgradeModel->setDatabase($this->dbo ?? Factory::getContainer()->get('DatabaseDriver'));
		}
		elseif (method_exists($upgradeModel, 'setDbo'))
		{
			$upgradeModel->setDbo($this->dbo ?? Factory::getContainer()->get('DatabaseDriver'));
		}

		if (method_exists($upgradeModel, 'init'))
		{
			$upgradeModel->init();
		}

		return $upgradeModel;
	}

	/**
	 * Does at least one instance of a given module exist in the specified dashboard?
	 *
	 * @param   string  $dashboard  The dashboard to check
	 * @param   string  $module     The module to check, e.g. mod_example
	 *
	 * @return  bool
	 * @since   7.1.0
	 */
	private function isModuleInDashboard(string $dashboard, string $module): bool
	{
		$position = 'cpanel-' . $dashboard;
		$db       = Factory::getContainer()->get('DatabaseDriver');
		$query    = $db->getQuery(true)
		               ->select('COUNT(*)')
		               ->from($db->quoteName('#__modules'))
		               ->where([
			               $db->quoteName('module') . ' = :module',
			               $db->quoteName('client_id') . ' = ' . $db->quote(1),
			               $db->quoteName('position') . ' = :position',
		               ])
		               ->bind(':module', $module, ParameterType::STRING)
		               ->bind(':position', $position, ParameterType::STRING);

		$modules = $db->setQuery($query)->loadResult() ?: 0;

		return $modules > 0;
	}

	/**
	 * Set the database object from the installation adapter, if possible
	 *
	 * @param   InstallerAdapter|mixed  $adapter  The installation adapter, hopefully.
	 *
	 * @return  void
	 * @since   7.1.0
	 */
	private function setDboFromAdapter($adapter): void
	{
		$this->dbo = null;

		if (class_exists(InstallerAdapter::class) && ($adapter instanceof InstallerAdapter))
		{
			/**
			 * If this is Joomla 4.2+ the adapter has a protected getDatabase() method which we can access with the
			 * magic property $adapter->db. On Joomla 4.1 and lower this is not available. So, we have to first figure
			 * out if we can actually use the magic property...
			 */

			try
			{
				$refObj = new ReflectionObject($adapter);

				if ($refObj->hasMethod('getDatabase'))
				{
					$this->dbo = $adapter->db;

					return;
				}
			}
			catch (Throwable $e)
			{
				// If something breaks we will fall through
			}
		}

		$this->dbo = Factory::getContainer()->get('DatabaseDriver');
	}

	private function removeOldUpdateSites()
	{
		$db    = $this->dbo;
		$query = $db->getQuery(true)
			->delete($db->qn('#__update_sites'))
			->where($db->qn('location') . ' = ' . $db->q('https://raw.githubusercontent.com/akeeba/release-system/master/update/pkg_ars_updates.xml'));
		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			// Do nothing on failure
		}
	}

}
