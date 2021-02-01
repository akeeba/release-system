<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\ComponentAdapter;

defined('_JEXEC') or die();

// Load FOF if not already loaded
if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	throw new RuntimeException('This component requires FOF 4.0.');
}


class Com_ArsInstallerScript extends \FOF40\InstallScript\Component
{
	/**
	 * The component's name
	 *
	 * @var   string
	 */
	public $componentName = 'com_ars';

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
	protected $minimumPHPVersion = '7.3.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.9.0';

	/**
	 * The list of obsolete extra modules and plugins to uninstall on component upgrade / installation.
	 *
	 * @var array
	 */
	protected $uninstallation_queue = [
		// modules => { (folder) => { (module) }* }*
		'modules' => [
			'admin' => [],
			'site'  => [],
		],
		// plugins => { (folder) => { (element) }* }*
		'plugins' => [
			'ars' => [
				'bleedingedgediff',
				'bleedingedgematurity',
			],
		],
	];

	/**
	 * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
	 * some files inevitably become obsolete and need to be removed.
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = [
		'files'   => [
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

			// Version 5 -- Rolling internal release
			'administrator/components/com_ars/Helper/ComponentParams.php',
			'administrator/components/com_ars/Helper/AmazonS3.php',

			'administrator/components/com_ars/Model/Mixin/Assertions.php',
			'administrator/components/com_ars/Model/Mixin/DateManipulation.php',
			'administrator/components/com_ars/Model/Mixin/ImplodedArrays.php',
			'administrator/components/com_ars/Model/Mixin/JsonData.php',

			'administrator/components/com_ars/Model/VisualGroups.php',

			'components/com_ars/Helper/Title.php',

			'components/com_ars/Model/VisualGroups.php',

			'components/com_ars/View/Update/tmpl/jed.php',

			'components/com_ars/views/Update/tmpl/jed.xml',

			'media/com_ars/js/jqplot.dateAxisRenderer.min.js',
			'media/com_ars/js/jqplot.hermite.js',
			'media/com_ars/js/jqplot.hihglighter.min.js',
			'media/com_ars/js/jquery.colorhelpers.min.js',
			'media/com_ars/js/jquery.jqplot.min.js',

			// Removed GeoIP features
			'administrator/components/com_ars/ViewTemplates/ControlPanel/geoip.blade.php',

			// Unused bitmap icons
			'media/com_ars/icons/action_add.png',
			'media/com_ars/icons/action_apply.png',
			'media/com_ars/icons/action_cancel.png',
			'media/com_ars/icons/action_csv.png',
			'media/com_ars/icons/action_download.png',
			'media/com_ars/icons/action_edit.png',
			'media/com_ars/icons/action_pdf.png',
			'media/com_ars/icons/action_remove.png',
			'media/com_ars/icons/action_save.png',

			'media/com_ars/icons/ars_logo_16.png',
			'media/com_ars/icons/ars_logo_32.png',
			'media/com_ars/icons/ars_logo_64.png',

			'media/com_ars/icons/view-activity-32.png',
			'media/com_ars/icons/view-autodesc-32.png',
			'media/com_ars/icons/view-categories-32.png',
			'media/com_ars/icons/view-dlidlabels-32.png',
			'media/com_ars/icons/view-impjed-32.png',
			'media/com_ars/icons/view-items-32.png',
			'media/com_ars/icons/view-logs-32.png',
			'media/com_ars/icons/view-releases-32.png',
			'media/com_ars/icons/view-updatestreams-32.png',
			'media/com_ars/icons/view-upload-32.png',

			'media/com_ars/icons/browse_16.png',
			'media/com_ars/icons/error_small.png',
			'media/com_ars/icons/locked_16.png',
			'media/com_ars/icons/ok_small.png',
			'media/com_ars/icons/unlocked_16.png',

			'media/com_ars/icons/status_alpha.png',
			'media/com_ars/icons/status_beta.png',
			'media/com_ars/icons/status_rc.png',
			'media/com_ars/icons/status_stable.png',

			// Unused CSS file
			'media/com_ars/css/browser.css',

			// Subscription levels support
			'administrator/components/com_ars/Model/SubscriptionIntegration.php',

			// Moved inside FOF
			'administrator/components/com_ars/ViewTemplates/Common/browse.blade.php',
			'administrator/components/com_ars/ViewTemplates/Common/edit.blade.php',
			'administrator/components/com_ars/ViewTemplates/Common/EntryUser.blade.php',
			'administrator/components/com_ars/ViewTemplates/Common/ShowUser.blade.php',

			// Moving to Akeeba FEF 2
			'media/com_ars/js/Ajax.js',
			'media/com_ars/js/Ajax.min.js',
			'media/com_ars/js/Ajax.min.map',
			'media/com_ars/js/Modal.js',
			'media/com_ars/js/Modal.min.js',
			'media/com_ars/js/Modal.min.map',
			'media/com_ars/js/System.js',
			'media/com_ars/js/System.min.js',
			'media/com_ars/js/System.min.map',
			'media/com_ars/js/Tooltip.js',
			'media/com_ars/js/Tooltip.min.js',
			'media/com_ars/js/Tooltip.min.map',
		],
		'folders' => [
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

			// Environment icons
			'media/com_ars/environments',

			// Common tables (they're installed by FOF)
			'administrator/components/com_ars/sql/common',

			// Unused files
			'administrator/components/com_ars/Helper/IniParser.php',

			// Version 5 -- Rolling internal release
			'administrator/components/com_ars/assets',
			'administrator/components/com_ars/vendor',

			'administrator/components/com_ars/Controller/Mixin',

			'administrator/components/com_ars/Model/SubscriptionIntegration',

			'administrator/components/com_ars/View/AutoDescriptions/tmpl',
			'administrator/components/com_ars/View/Categories/tmpl',
			'administrator/components/com_ars/View/ControlPanel/tmpl',
			'administrator/components/com_ars/View/DownloadIDLabels/tmpl',
			'administrator/components/com_ars/View/Environments/tmpl',
			'administrator/components/com_ars/View/Items/tmpl',
			'administrator/components/com_ars/View/Logs/tmpl',
			'administrator/components/com_ars/View/Releases/tmpl',
			'administrator/components/com_ars/View/UpdateStreams/tmpl',
			'administrator/components/com_ars/View/VisualGroups',

			'components/com_ars/View/Categories/tmpl',
			'components/com_ars/View/DownloadIDLabels/tmpl',
			'components/com_ars/View/Items/tmpl',
			'components/com_ars/View/Latest/tmpl',
			'components/com_ars/View/Releases/tmpl',
			'components/com_ars/View/Update/tmpl',

			// Joomla! 4 compatible routing (using a router class instead of router functions)
			'components/com_ars/Helper/ComArsRouter.php',
			'components/com_ars/Helper/ArsRouterHelper.php',
		],
	];

	public function uninstall(ComponentAdapter $parent): void
	{
		// Remove the update sites for this component on installation. The update sites are now handled at the package
		// level.
		$this->removeObsoleteUpdateSites($parent);

		parent::uninstall($parent);
	}

	public function postflight(string $type, ComponentAdapter $parent): void
	{
		parent::postflight($type, $parent);

		// Add ourselves to the list of extensions depending on Akeeba FEF
		$this->addDependency('file_fef', $this->componentName);
	}

	/**
	 * Renders the post-installation message
	 */
	protected function renderPostInstallation(ComponentAdapter $parent): void
	{
		$this->warnAboutJSNPowerAdmin();

		?>
		<h1>Akeeba Release System</h1>

		<img src="../media/com_ars/icons/ars_logo_48.png" width="48" height="48" alt="Akeeba Release System"
			 align="right" />

		<h2>Welcome to Akeeba Release System!</h2>

		<?php
	}

	protected function renderPostUninstallation(ComponentAdapter $parent): void
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
		$db            = Factory::getDbo();
		$query         = $db->getQuery(true)
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

		$query         = $db->getQuery(true)
			->select('manifest_cache')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
			->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$paramsJson    = $db->setQuery($query)->loadResult();
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
	 * @param JInstallerAdapterComponent $parent The parent installer
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
