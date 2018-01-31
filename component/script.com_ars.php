<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die();

// Load FOF if not already loaded
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('This component requires FOF 3.0.');
}


class Com_ArsInstallerScript extends \FOF30\Utils\InstallScript
{
	/**
	 * The component's name
	 *
	 * @var   string
	 */
	protected $componentName = 'com_ars';

	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $componentTitle = 'Akeeba Release System';

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '5.4.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.4.0';

	/**
	 * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
	 * some files inevitably become obsolete and need to be removed.
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = array(
		'files'   => array(
			// Moving to FOF 3
			'cache/com_ars.updates.php',
			'cache/com_ars.updates.ini',

			'administrator/cache/com_ars.updates.php',
			'administrator/cache/com_ars.updates.ini',

			'administrator/components/com_ars/install.sql',
			'administrator/components/com_ars/uninstall.sql',
			'administrator/components/com_ars/assets/cacert.pem',
			'administrator/components/com_ars/dispatcher.php',
			'administrator/components/com_ars/install.ars.php',
			'administrator/components/com_ars/script.ars.php',
			'administrator/components/com_ars/toolbar.php',
			'administrator/components/com_ars/uninstall.ars.php',

			'components/com_ars/views/view.html.php',
			'components/com_ars/dispatcher.php',
			'components/com_ars/views/Releases/tmpl/item.xml',

			'media/com_ars/js/akeebajq.js',
			'media/com_ars/js/akeebajqui.js',

			// Updates
			'administrator/components/com_ars/Controller/Updates.php',
			'administrator/components/com_ars/Model/Updates.php',
			'administrator/components/com_ars/Model/Statistics.php',
			'administrator/components/com_ars/assets/stats',
			'components/com_ars/Model/ExtensionUpdates.php',
			'cli/ars-update',

            // Moving to FEF
            'administrator/components/com_ars/Controller/Upload.php',
            'administrator/components/com_ars/Model/Upload.php',
            'administrator/components/com_ars/View/views.ini',
			'administrator/components/com_ars/View/AutoDescriptions/tmpl/form.default.xml',
            'administrator/components/com_ars/View/AutoDescriptions/tmpl/form.form.xml',
			'administrator/components/com_ars/View/Categories/tmpl/form.form.xml',
			'administrator/components/com_ars/View/Categories/tmpl/form.default.xml',
            'administrator/components/com_ars/View/DownloadIDLabels/tmpl/form.default.xml',
            'administrator/components/com_ars/View/DownloadIDLabels/tmpl/form.form.xml',
            'administrator/components/com_ars/View/DownloadIDLabels/tmpl/default_dlid.php',
            'administrator/components/com_ars/View/Environments/tmpl/form.default.xml',
            'administrator/components/com_ars/View/Environments/tmpl/form.form.xml',
            'administrator/components/com_ars/View/Items/tmpl/form.form.xml',
            'administrator/components/com_ars/View/Items/tmpl/default_environments.php',
            'administrator/components/com_ars/View/Items/tmpl/form.default.xml',
            'administrator/components/com_ars/View/Items/tmpl/form.modal.xml',
            'administrator/components/com_ars/View/Logs/tmpl/form.default.xml',
            'administrator/components/com_ars/View/Logs/tmpl/default_item.php',
            'administrator/components/com_ars/View/Releases/tmpl/form.form.xml',
            'administrator/components/com_ars/View/Releases/tmpl/form.default.xml',
            'administrator/components/com_ars/View/UpdateStreams/tmpl/default_links.php',
            'administrator/components/com_ars/View/UpdateStreams/tmpl/form.default.xml',
            'administrator/components/com_ars/View/UpdateStreams/tmpl/form.form.xml',
            'administrator/components/com_ars/View/VisualGroups/tmpl/form.form.xml',
            'administrator/components/com_ars/View/VisualGroups/tmpl/form.default.xml',

            'components/com_ars/View/DownloadIDLabels/Form.php',
            'components/com_ars/View/DownloadIDLabels/tmpl/form.form.xml',
            'components/com_ars/View/Items/Form.php',
            'components/com_ars/View/Items/tmpl/form.modal.xml',
		),
		'folders' => array(
			// Moving to FOF 3
			'administrator/components/com_ars/assets/geoip',
			'administrator/components/com_ars/assets/images',
			'administrator/components/com_ars/controllers',
			'administrator/components/com_ars/fields',
			'administrator/components/com_ars/helpers',
			'administrator/components/com_ars/models',
			'administrator/components/com_ars/tables',
			'administrator/components/com_ars/views',
			'administrator/components/com_ars/elements',
			'administrator/components/com_ars/language',

			// Old Amazon integration
			'administrator/components/com_ars/Amazon',

			// Usage stats
			'administrator/components/com_ars/assets/stats',

			'components/com_ars/controllers',
			'components/com_ars/helpers',
			'components/com_ars/models',
			'components/com_ars/views/browse',
			'components/com_ars/views/browses',
			'components/com_ars/views/category',
			'components/com_ars/views/dlidlabel',
			'components/com_ars/views/dlidlabels',
			'components/com_ars/views/download',
			'components/com_ars/views/latests',
			'components/com_ars/views/release',

			'media/com_ars/theme',

			// Moving to FEF
            'administrator/components/com_ars/Form',
            'administrator/components/com_ars/View/Upload',
		)
	);

	public function uninstall($parent)
	{
		// Remove the update sites for this component on installation. The update sites are now handled at the package
		// level.
		$this->removeObsoleteUpdateSites($parent);

		parent::uninstall($parent);
	}

	public function postflight($type, $parent)
	{
		parent::postflight($type, $parent);

		// Add ourselves to the list of extensions depending on Akeeba FEF
		$this->addDependency('file_fef', $this->componentName);
	}

	/**
	 * Renders the post-installation message
	 */
	protected function renderPostInstallation($parent)
	{
		$this->warnAboutJSNPowerAdmin();

		?>
		<h1>Akeeba Release System</h1>

		<img src="../media/com_ars/icons/ars_logo_48.png" width="48" height="48" alt="Akeeba Release System"
			 align="right"/>

		<h2>Welcome to Akeeba Release System!</h2>

		<?php
	}

	protected function renderPostUninstallation($parent)
	{
		?>
		<h2>Akeeba Release System Uninstalation</h2>
		<?php
		parent::renderPostUninstallation($parent);
	}


	/**
	 * The PowerAdmin extension makes menu items disappear. People assume it's our fault. JSN PowerAdmin authors don't
	 * own up to their software's issue. I have no choice but to warn our users about the faulty third party software.
	 */
	private function warnAboutJSNPowerAdmin()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
					->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$hasPowerAdmin = $db->setQuery($query)->loadResult();

		if (!$hasPowerAdmin)
		{
			return;
		}

		$query = $db->getQuery(true)
					->select('manifest_cache')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
					->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$paramsJson = $db->setQuery($query)->loadResult();
		$jsnPAManifest = new JRegistry();
		$jsnPAManifest->loadString($paramsJson, 'JSON');
		$version = $jsnPAManifest->get('version', '0.0.0');

		if (version_compare($version, '2.1.2', 'ge'))
		{
			return;
		}

		echo <<< HTML
<div class="well" style="margin: 2em 0;">
<h1 style="font-size: 32pt; line-height: 120%; color: red; margin-bottom: 1em">WARNING: Menu items for {$this->componentName} might not be displayed on your site.</h1>
<p style="font-size: 18pt; line-height: 150%; margin-bottom: 1.5em">
	We have detected that you are using JSN PowerAdmin on your site. This software ignores Joomla! standards and
	<b>hides</b> the Component menu items to {$this->componentName} in the administrator backend of your site. Unfortunately we
	can't provide support for third party software. Please contact the developers of JSN PowerAdmin for support
	regarding this issue.
</p>
<p style="font-size: 18pt; line-height: 120%; color: green;">
	Tip: You can disable JSN PowerAdmin to see the menu items to {$this->componentName}.
</p>
</div>

HTML;

	}

	/**
	 * Removes obsolete update sites created for the component (we are now using an update site for the package, not the
	 * component).
	 *
	 * @param   JInstallerAdapterComponent  $parent  The parent installer
	 */
	protected function removeObsoleteUpdateSites($parent)
	{
		$db = $parent->getParent()->getDBO();

		$query = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('name') . ' = ' . $db->q($this->componentName));
		$db->setQuery($query);
		$extensionId = $db->loadResult();

		if (!$extensionId)
		{
			return;
		}

		$query = $db->getQuery(true)
					->select($db->qn('update_site_id'))
					->from($db->qn('#__update_sites_extensions'))
					->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$ids = $db->loadColumn(0);

		if (!is_array($ids) && empty($ids))
		{
			return;
		}

		foreach ($ids as $id)
		{
			$query = $db->getQuery(true)
						->delete($db->qn('#__update_sites'))
						->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\Exception $e)
			{
				// Do not fail in this case
			}
		}
	}

}
