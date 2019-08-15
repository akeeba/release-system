<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die();

class Pkg_ArsInstallerScript
{
	/**
	 * The name of our package, e.g. pkg_example. Used for dependency tracking.
	 *
	 * @var  string
	 */
	protected $packageName = 'pkg_ars';

	/**
	 * The name of our component, e.g. com_example. Used for dependency tracking.
	 *
	 * @var  string
	 */
	protected $componentName = 'com_ars';

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '7.3.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.9.0';

	/**
	 * The maximum Joomla! version this extension can be installed on
	 *
	 * @var   string
	 */
	protected $maximumJoomlaVersion = '4.0.99';

	/**
	 * A list of extensions (modules, plugins) to enable after installation. Each item has four values, in this order:
	 * type (plugin, module, ...), name (of the extension), client (0=site, 1=admin), group (for plugins).
	 *
	 * @var array
	 */
	protected $extensionsToEnable = [
		['plugin', 'arslatest', 1, 'content'],
		['plugin', 'arslink', 1, 'editors-xtd'],
		['plugin', 'arsjed', 1, 'system'],
	];

	/**
	 * =================================================================================================================
	 * DO NOT EDIT BELOW THIS LINE
	 * =================================================================================================================
	 */

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the package. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * In here we'll try to install FOF. We have to do that before installing the component since it's using an
	 * installation script extending FOF's InstallScript class. We can't use a <file> tag in the manifest to install FOF
	 * since the FOF installation is expected to fail if a newer version of FOF is already installed on the site.
	 *
	 * @param   string                     $type    Installation type (install, update, discover_install)
	 * @param   \JInstallerAdapterPackage  $parent  Parent object
	 *
	 * @return  boolean  True to let the installation proceed, false to halt the installation
	 */
	public function preflight($type, $parent)
	{
		// Check the minimum PHP version
		if (!version_compare(PHP_VERSION, $this->minimumPHPVersion, 'ge'))
		{
			$msg = "<p>You need PHP $this->minimumPHPVersion or later to install this package</p>";
			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Check the minimum Joomla! version
		if (!version_compare(JVERSION, $this->minimumJoomlaVersion, 'ge'))
		{
			$msg = "<p>You need Joomla! $this->minimumJoomlaVersion or later to install this component</p>";
			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Check the maximum Joomla! version
		if (!version_compare(JVERSION, $this->maximumJoomlaVersion, 'le'))
		{
			$msg = "<p>You need Joomla! $this->maximumJoomlaVersion or earlier to install this component</p>";
			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Try to install FOF. We need to do this in preflight to make sure that FOF is available when we install our
		// component. The reason being that the component's installation script extends FOF's InstallScript class.
		// We can't use a <file> tag in our package manifest because FOF's package is *supposed* to fail to install if
		// a newer version is already installed. This would unfortunately cancel the installation of the entire package,
		// so we have to get a bit tricky.
		$this->installOrUpdateFOF($parent);

		return true;
	}

	/**
	 * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
	 * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
	 * database updates and similar housekeeping functions.
	 *
	 * @param   string                       $type   install, update or discover_update
	 * @param   \JInstallerAdapterComponent  $parent Parent object
	 */
	public function postflight($type, $parent)
	{
		/**
		 * Try to install FEF. We only need to do this in postflight. A failure, while detrimental to the display of the
		 * extension, is non-fatal to the installation and can be rectified by manual installation of the FEF package.
		 * We can't use a <file> tag in our package manifest because FEF's package is *supposed* to fail to install if
		 * a newer version is already installed. This would unfortunately cancel the installation of the entire package,
		 * so we have to get a bit tricky.
		 */
		$this->installOrUpdateFEF($parent);

		/**
		 * Clean the cache after installing the package.
		 *
		 * See bug report https://github.com/joomla/joomla-cms/issues/16147
		 */
		$conf = \JFactory::getConfig();
		$clearGroups = array('_system', 'com_modules', 'mod_menu', 'com_plugins', 'com_modules');
		$cacheClients = array(0, 1);

		foreach ($clearGroups as $group)
		{
			foreach ($cacheClients as $client_id)
			{
				try
				{
					$options = array(
						'defaultgroup' => $group,
						'cachebase' => ($client_id) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
					);

					/** @var JCache $cache */
					$cache = \JCache::getInstance('callback', $options);
					$cache->clean();
				}
				catch (Exception $exception)
				{
					$options['result'] = false;
				}

				// Trigger the onContentCleanCache event.
				try
				{
					JFactory::getApplication()->triggerEvent('onContentCleanCache', $options);
				}
				catch (Exception $e)
				{
					// Suck it up
				}
			}
		}
	}

	/**
	 * Tuns on installation (but not on upgrade). This happens in install and discover_install installation routes.
	 *
	 * @param   \JInstallerAdapterPackage  $parent  Parent object
	 *
	 * @return  bool
	 */
	public function install($parent)
	{
		// Enable the extensions we need to install
		$this->enableExtensions();

		// Remove strapper30 dependency for our package (it's no longer necessary)
		$this->removeDependency('strapper30', $this->packageName);

		return true;
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   \JInstallerAdapterPackage  $parent  Parent object
	 *
	 * @return  bool
	 */
	public function uninstall($parent)
	{
		// Preload FOF classes required for the InstallScript. This is required since we'll be trying to uninstall FOF
		// before uninstalling the component itself. The component has an uninstallation script which uses FOF, so...
		@include_once(JPATH_LIBRARIES . '/fof30/include.php');
		class_exists('FOF30\\Utils\\InstallScript\\BaseInstaller', true);
		class_exists('FOF30\\Utils\\InstallScript\\Component', true);
		class_exists('FOF30\\Utils\\InstallScript\\Module', true);
		class_exists('FOF30\\Utils\\InstallScript\\Plugin', true);
		class_exists('FOF30\\Utils\\InstallScript');
		class_exists('FOF30\\Database\\Installer');

		/**
		 * uninstall() is called before the component is uninstalled. Therefore there is a dependency to FOF 3 which
		 * prevents FOF 3 from being removed at this point. Therefore we have to remove the dependency before removing
		 * the component and hope nothing goes wrong.
		 */
		$this->removeDependency('fof30', $this->componentName);

		/**
		 * uninstall() is called before the component is uninstalled. Therefore there is a dependency to FEF which
		 * prevents FEF from being removed at this point. Therefore we have to remove the dependency before removing
		 * the component and hope nothing goes wrong.
		 */
		$this->removeDependency('file_fef', $this->componentName);

		// The try to uninstall FEF. The uninstallation might fail if there are other extensions depending
		// on it. That would cause the entire package uninstallation to fail, hence the need for special handling.
		$this->uninstallFEF($parent);

		// Then try to uninstall the FOF library. The uninstallation might fail if there are other extensions depending
		// on it. That would cause the entire package uninstallation to fail, hence the need for special handling.
		$this->uninstallFOF($parent);

		return true;
	}

	/**
	 * Tries to install or update FOF. The FOF library package installation can fail if there's a newer version
	 * installed. In this case we raise no error. If, however, the FOF library package installation failed AND we can
	 * not load FOF then we raise an error: this means that FOF installation really failed (e.g. unwritable folder) and
	 * we can't install this package.
	 *
	 * @param   \JInstallerAdapterPackage  $parent
	 */
	private function installOrUpdateFOF($parent)
	{
		// Get the path to the FOF package
		$sourcePath = $parent->getParent()->getPath('source');
		$sourcePackage = $sourcePath . '/lib_fof30.zip';

		// Extract and install the package
		$package = JInstallerHelper::unpack($sourcePackage);
		$tmpInstaller  = new JInstaller;
		$error = null;

		try
		{
			$installResult = $tmpInstaller->install($package['dir']);
		}
		catch (\Exception $e)
		{
			$installResult = false;
			$error = $e->getMessage();
		}

		// Try to include FOF. If that fails then the FOF package isn't installed because its installation failed, not
		// because we had a newer version already installed. As a result we have to abort the entire package's
		// installation.
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			if (empty($error))
			{
				$error = JText::sprintf(
					'JLIB_INSTALLER_ABORT_PACK_INSTALL_ERROR_EXTENSION',
					JText::_('JLIB_INSTALLER_' . strtoupper($parent->get('route'))),
					basename($sourcePackage)
				);
			}

			throw new RuntimeException($error);
		}
	}

	/**
	 * Try to uninstall the FOF library. We don't go through the Joomla! package uninstallation since we can expect the
	 * uninstallation of the FOF library to fail if other software depends on it.
	 *
	 * @param   JInstallerAdapterPackage  $parent
	 */
	private function uninstallFOF($parent)
	{
		$tmpInstaller = new JInstaller;

		$db = $parent->getParent()->getDbo();

		$query = $db->getQuery(true)
		            ->select('extension_id')
		            ->from('#__extensions')
		            ->where('type = ' . $db->quote('library'))
		            ->where('element = ' . $db->quote('lib_fof30'));

		$db->setQuery($query);
		$id = $db->loadResult();

		if (!$id)
		{
			return;
		}

		try
		{
			$tmpInstaller->uninstall('library', $id, 0);
		}
		catch (\Exception $e)
		{
			// We can expect the uninstallation to fail if there are other extensions depending on the FOF library.
		}
	}

	/**
	 * Tries to install or update FEF. The FEF files package installation can fail if there's a newer version
	 * installed.
	 *
	 * @param   \JInstallerAdapterPackage  $parent
	 */
	private function installOrUpdateFEF($parent)
	{
		// Get the path to the FOF package
		$sourcePath = $parent->getParent()->getPath('source');
		$sourcePackage = $sourcePath . '/file_fef.zip';

		// Extract and install the package
		$package = JInstallerHelper::unpack($sourcePackage);
		$tmpInstaller  = new JInstaller;
		$error = null;

		try
		{
			$installResult = $tmpInstaller->install($package['dir']);
		}
		catch (\Exception $e)
		{
			$installResult = false;
			$error = $e->getMessage();
		}
	}

	/**
	 * Try to uninstall the FEF package. We don't go through the Joomla! package uninstallation since we can expect the
	 * uninstallation of the FEF library to fail if other software depends on it.
	 *
	 * @param   JInstallerAdapterPackage  $parent
	 */
	private function uninstallFEF($parent)
	{
		// Check dependencies on FOF
		$dependencyCount = count($this->getDependencies('file_fef'));

		if ($dependencyCount)
		{
			$msg = "<p>You have $dependencyCount extension(s) depending on this version of Akeeba FEF. The package cannot be uninstalled unless these extensions are uninstalled first.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return;
		}

		$tmpInstaller = new JInstaller;

		$db = $parent->getParent()->getDbo();

		$query = $db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where('type = ' . $db->quote('file'))
			->where('element = ' . $db->quote('file_fef'));

		$db->setQuery($query);
		$id = $db->loadResult();

		if (!$id)
		{
			return;
		}

		try
		{
			$tmpInstaller->uninstall('file', $id, 0);
		}
		catch (\Exception $e)
		{
			// We can expect the uninstallation to fail if there are other extensions depending on the FOF library.
		}
	}

	/**
	 * Enable modules and plugins after installing them
	 */
	private function enableExtensions()
	{
		$db = JFactory::getDbo();

		foreach ($this->extensionsToEnable as $ext)
		{
			$this->enableExtension($ext[0], $ext[1], $ext[2], $ext[3]);
		}
	}

	/**
	 * Enable an extension
	 *
	 * @param   string   $type    The extension type.
	 * @param   string   $name    The name of the extension (the element field).
	 * @param   integer  $client  The application id (0: Joomla CMS site; 1: Joomla CMS administrator).
	 * @param   string   $group   The extension group (for plugins).
	 */
	private function enableExtension($type, $name, $client = 1, $group = null)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
					->update('#__extensions')
					->set($db->qn('enabled') . ' = ' . $db->q(1))
		            ->where('type = ' . $db->quote($type))
		            ->where('element = ' . $db->quote($name));

		switch ($type)
		{
			case 'plugin':
				// Plugins have a folder but not a client
				$query->where('folder = ' . $db->quote($group));
				break;

			case 'language':
			case 'module':
			case 'template':
				// Languages, modules and templates have a client but not a folder
				$client = JApplicationHelper::getClientInfo($client, true);
				$query->where('client_id = ' . (int) $client->id);
				break;

			default:
			case 'library':
			case 'package':
			case 'component':
				// Components, packages and libraries don't have a folder or client.
				// Included for completeness.
				break;
		}

		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (\Exception $e)
		{
		}
	}

	/**
	 * Get the dependencies for a package from the #__akeeba_common table
	 *
	 * @param   string  $package  The package
	 *
	 * @return  array  The dependencies
	 */
	protected function getDependencies($package)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
		            ->select($db->qn('value'))
		            ->from($db->qn('#__akeeba_common'))
		            ->where($db->qn('key') . ' = ' . $db->q($package));

		try
		{
			$dependencies = $db->setQuery($query)->loadResult();
			$dependencies = json_decode($dependencies);

			if (empty($dependencies))
			{
				$dependencies = array();
			}
		}
		catch (Exception $e)
		{
			$dependencies = array();
		}

		return $dependencies;
	}

	/**
	 * Sets the dependencies for a package into the #__akeeba_common table
	 *
	 * @param   string  $package       The package
	 * @param   array   $dependencies  The dependencies list
	 */
	protected function setDependencies($package, array $dependencies)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
		            ->delete('#__akeeba_common')
		            ->where($db->qn('key') . ' = ' . $db->q($package));

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			// Do nothing if the old key wasn't found
		}

		$object = (object)array(
			'key' => $package,
			'value' => json_encode($dependencies)
		);

		try
		{
			$db->insertObject('#__akeeba_common', $object, 'key');
		}
		catch (Exception $e)
		{
			// Do nothing if the old key wasn't found
		}
	}

	/**
	 * Adds a package dependency to #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to add
	 */
	protected function addDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		if (!in_array($dependency, $dependencies))
		{
			$dependencies[] = $dependency;

			$this->setDependencies($package, $dependencies);
		}
	}

	/**
	 * Removes a package dependency from #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to remove
	 */
	protected function removeDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		if (in_array($dependency, $dependencies))
		{
			$index = array_search($dependency, $dependencies);
			unset($dependencies[$index]);

			$this->setDependencies($package, $dependencies);
		}
	}

	/**
	 * Do I have a dependency for a package in #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to check for
	 *
	 * @return bool
	 */
	protected function hasDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		return in_array($dependency, $dependencies);
	}
}
