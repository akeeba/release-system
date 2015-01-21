<?php
/**
 * @package   AkeebaBackup
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 *
 */
defined('_JEXEC') or die();

// Load FOF if not already loaded
if (!defined('F0F_INCLUDED'))
{
	$paths = array(
		(defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/f0f/include.php',
		__DIR__ . '/fof/include.php',
	);

	foreach ($paths as $filePath)
	{
		if (!defined('F0F_INCLUDED') && file_exists($filePath))
		{
			@include_once $filePath;
		}
	}
}

// Pre-load the installer script class from our own copy of FOF
if (!class_exists('F0FUtilsInstallscript', false))
{
	@include_once __DIR__ . '/fof/utils/installscript/installscript.php';
}

// Pre-load the database schema installer class from our own copy of FOF
if (!class_exists('F0FDatabaseInstaller', false))
{
	@include_once __DIR__ . '/fof/database/installer.php';
}

// Pre-load the update utility class from our own copy of FOF
if (!class_exists('F0FUtilsUpdate', false))
{
	@include_once __DIR__ . '/fof/utils/update/update.php';
}

// Pre-load the cache cleaner utility class from our own copy of FOF
if (!class_exists('F0FUtilsCacheCleaner', false))
{
	@include_once __DIR__ . '/fof/utils/cache/cleaner.php';
}

class Com_ArsInstallerScript extends F0FUtilsInstallscript
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
	 * The list of extra modules and plugins to install on component installation / update and remove on component
	 * uninstallation.
	 *
	 * @var   array
	 */
	protected $installation_queue = array(
		// modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(),
			'site'  => array(
				'arsdlid'      => array('left', 0),
				'arsdownloads' => array('left', 0),
			)
		),
		// plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'ars'                => array(
				'bleedingedgediff'     => 0,
				'bleedingedgematurity' => 0,
			),
			'content'            => array(
				'arsdlid'   => 0,
				'arslatest' => 1,
			),
			'editors-xtd'        => array(
				'arslink' => 1,
			),
			'sh404sefextplugins' => array(
				'com_ars' => 1,
			),
			'system'             => array(
				'arsjed' => 1,
			),
		)
	);

	/**
	 * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
	 * some files inevitably become obsolete and need to be removed.
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = array(
		'files'   => array(
			'cache/com_ars.updates.php',
			'cache/com_ars.updates.ini',
			'administrator/cache/com_ars.updates.php',
			'administrator/cache/com_ars.updates.ini',

			'administrator/components/com_ars/install.sql',
			'administrator/components/com_ars/uninstall.sql',
			'administrator/components/com_ars/controllers/categories.php',
			'administrator/components/com_ars/controllers/default.php',
			'administrator/components/com_ars/controllers/items.php',
			'administrator/components/com_ars/controllers/logs.php',
			'administrator/components/com_ars/controllers/releases.php',
			'administrator/components/com_ars/controllers/updatestreams.php',
			'administrator/components/com_ars/controllers/vgroups.php',
			'administrator/components/com_ars/elements/styles.php',
			'administrator/components/com_ars/helpers/includes.php',
			'administrator/components/com_ars/models/autodesc.php',
			'administrator/components/com_ars/models/base.php',
			'administrator/components/com_ars/models/cpanel.php',
			'administrator/components/com_ars/models/filtering.php',
			'administrator/components/com_ars/models/impjed.php',
			'administrator/components/com_ars/models/upload.php',
			'administrator/components/com_ars/tables/base.php',
			'administrator/components/com_ars/tables/categories.php',
			'administrator/components/com_ars/tables/environments.php',
			'administrator/components/com_ars/tables/items.php',
			'administrator/components/com_ars/tables/logs.php',
			'administrator/components/com_ars/tables/releases.php',
			'administrator/components/com_ars/tables/updatestreams.php',
			'administrator/components/com_ars/tables/vgroups.php',
			'administrator/components/com_ars/views/base.view.html.php',
			'administrator/components/com_ars/views/autodesc/view.html.php',
			'administrator/components/com_ars/views/autodesc/tmpl/default.php',
			'administrator/components/com_ars/views/categories/view.html.php',
			'administrator/components/com_ars/views/categories/tmpl/form.php',
			'administrator/components/com_ars/views/environments/view.html.php',
			'administrator/components/com_ars/views/environments/tmpl/form.php',
			'administrator/components/com_ars/views/items/view.html.php',
			'administrator/components/com_ars/views/items/tmpl/form.php',
			'administrator/components/com_ars/views/logs/view.html.php',
			'administrator/components/com_ars/views/releases/view.html.php',
			'administrator/components/com_ars/views/releases/tmpl/form.php',
			'administrator/components/com_ars/views/updatestreams/view.html.php',
			'administrator/components/com_ars/views/updatestreams/tmpl/form.php',
			'administrator/components/com_ars/views/vgroups/view.html.php',
			'administrator/components/com_ars/views/vgroups/tmpl/form.php',
			'components/com_ars/controllers/default.php',
			'components/com_ars/helpers/includes.php',
			'components/com_ars/models/base.php',
			'components/com_ars/models/browse.php',
			'components/com_ars/models/category.php',
			'components/com_ars/models/download.php',
			'components/com_ars/models/release.php',
			'components/com_ars/models/update.php',
			'components/com_ars/views/view.html.php',
			'media/com_ars/js/akeebajq.js',
			'media/com_ars/js/akeebajqui.js',

			// Files from older versions
			'administrator/components/com_ars/views/vgroup/tmpl/form.php',
		),
		'folders' => array(
			'administrator/components/com_ars/assets/geoip',
			'administrator/components/com_ars/elements',
			'administrator/components/com_ars/language',
			'administrator/components/com_ars/views/cpanel',
			'administrator/components/com_ars/views/impjed',
			'administrator/components/com_ars/views/upload',
			'components/com_ars/views/browse',
			'components/com_ars/views/download',
			'components/com_ars/views/latest',
		)
	);

	/**
	 * A list of scripts to be copied to the "cli" directory of the site
	 *
	 * @var   array
	 */
	protected $cliScriptFiles = array(
		'ars-update.php'
	);

	/**
	 * Renders the post-installation message
	 */
	protected function renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent)
	{
		$this->warnAboutJSNPowerAdmin();

		?>
		<img src="../media/com_ars/icons/ars_logo_48.png" width="48" height="48" alt="Akeeba Release System"
			 align="right"/>

		<h2>Welcome to Akeeba Release System!</h2>

		<div style="margin: 1em; font-size: 14pt; background-color: #fffff9; color: black">
			You can download translation files <a href="http://cdn.akeebabackup.com/language/ars/index.html">directly
				from our CDN page</a>.
		</div>

		<?php
		parent::renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent);

        /** @var ArsModelStats $model */
        $model  = F0FModel::getTmpInstance('Stats', 'ArsModel');

        if(method_exists($model, 'collectStatistics'))
        {
            $iframe = $model->collectStatistics(true);

            if($iframe)
            {
                echo $iframe;
            }
        }
	}

	protected function renderPostUninstallation($status, $parent)
	{
		?>
		<h2>Akeeba Release System uninstallation status</h2>
		<?php
		parent::renderPostUninstallation($status, $parent);
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
	Tip: You can disable JSN PowerAdmin to see the menu items to Akeeba Backup.
</p>
</div>

HTML;

	}

}