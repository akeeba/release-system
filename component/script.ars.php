<?php
/**
 * @package AkeebaBackup
 * @copyright Copyright (c)2009-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 *
 */
defined('_JEXEC') or die();

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class Com_ArsInstallerScript
{
	/** @var string The component's name */
	protected $_akeeba_extension = 'com_ars';
	
	/** @var array The list of extra modules and plugins to install */
	private $installation_queue = array(
		// modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(
			),
			'site' => array(
				'arsdlid' => array('left', 0),
				'arsdownloads' => array('left', 0),
			)
		),
		// plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'ars' => array(
				'bleedingedgediff'		=> 0,
				'bleedingedgematurity'	=> 0,
			),
			'content' => array(
				'arsdlid'				=> 0,
			),
			'editors-xtd' => array(
				'arslink'				=> 0,
			),
		)
	);

	/** @var array Obsolete files and folders to remove */
	private $akeebaRemoveFiles = array(
		'files'	=> array(
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
		),
		'folders' => array(
			'administrator/components/com_ars/language',
			'administrator/components/com_ars/views/cpanel',
			'administrator/components/com_ars/views/impjed',
			'administrator/components/com_ars/views/upload',
			'components/com_ars/views/browse',
			'components/com_ars/views/download',
			'components/com_ars/views/latest',
		)
	);
	
	private $akeebaCliScripts = array(
	);

	
	/**
	 * Joomla! pre-flight event
	 * 
	 * @param string $type Installation type (install, update, discover_install)
	 * @param JInstaller $parent Parent object
	 */
	public function preflight($type, $parent)
	{
		// Bugfix for "Can not build admin menus"
		if(in_array($type, array('install','discover_install'))) {
			$this->_bugfixDBFunctionReturnedNoError();
		} else {
			$this->_bugfixCantBuildAdminMenus();
		}
		
		// Only allow to install on Joomla! 2.5.0 or later
		return version_compare(JVERSION, '2.5.0', 'ge');
	}
	
	/**
	 * Runs after install, update or discover_update
	 * @param string $type install, update or discover_update
	 * @param JInstaller $parent 
	 */
	function postflight( $type, $parent )
	{
		// Install subextensions
		$status = $this->_installSubextensions($parent);
		
		// Remove obsolete files and folders
		$this->_removeObsoleteFilesAndFolders($this->akeebaRemoveFiles);
		$this->_copyCliFiles($parent);
		
		// Install FOF
		$fofStatus = $this->_installFOF($parent);
		
		// Show the post-installation page
		$this->_renderPostInstallation($status, $fofStatus, $parent);
	}
	
	/**
	 * Runs on uninstallation
	 * 
	 * @param JInstaller $parent 
	 */
	function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);
		
		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}
	
	/**
	 * Copies the CLI scripts into Joomla!'s cli directory
	 * 
	 * @param JInstaller $parent 
	 */
	private function _copyCliFiles($parent)
	{
		if(!count($this->akeebaCliScripts)) return;
		
		$src = $parent->getParent()->getPath('source');
		
		jimport("joomla.filesystem.file");
		jimport("joomla.filesystem.folder");
		
		foreach($this->akeebaCliScripts as $script) {
			if(JFile::exists(JPATH_ROOT.'/cli/'.$script)) {
				JFile::delete(JPATH_ROOT.'/cli/'.$script);
			}
			if(JFile::exists($src.'/cli/'.$script)) {
				JFile::move($src.'/cli/'.$script, JPATH_ROOT.'/cli/'.$script);
			}
		}
	}
	
	/**
	 * Renders the post-installation message 
	 */
	private function _renderPostInstallation($status, $fofStatus, $parent)
	{
?>

<?php $rows = 0;?>
<img src="../media/com_ars/icons/ars_logo_48.png" width="48" height="48" alt="Akeeba Release System" align="right" />

<h2>Welcome to Akeeba Release System!</h2>

<div style="margin: 1em; font-size: 14pt; background-color: #fffff9; color: black">
	You can download translation files <a href="http://akeeba-cdn.s3-website-eu-west-1.amazonaws.com/language/ars/">directly from our CDN page</a>.
</div>

<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2">Extension</th>
			<th width="30%">Status</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2">Akeeba Release System component</td>
			<td><strong style="color: green">Installed</strong></td>
		</tr>
		<tr class="row1">
			<td class="key" colspan="2">
				<strong>Framework on Framework (FOF) <?php echo $fofStatus['version']?></strong> [<?php echo $fofStatus['date'] ?>]
			</td>
			<td><strong>
				<span style="color: <?php echo $fofStatus['required'] ? ($fofStatus['installed']?'green':'red') : '#660' ?>; font-weight: bold;">
					<?php echo $fofStatus['required'] ? ($fofStatus['installed'] ?'Installed':'Not Installed') : 'Already up-to-date'; ?>
				</span>	
			</strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th>Module</th>
			<th>Client</th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo ($rows++ % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><strong style="color: <?php echo ($module['result'])? "green" : "red"?>"><?php echo ($module['result'])?'Installed':'Not installed'; ?></strong></td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
		<?php if (count($status->plugins)) : ?>
		<tr>
			<th>Plugin</th>
			<th>Group</th>
			<th></th>
		</tr>
		<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo ($rows++ % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><strong style="color: <?php echo ($plugin['result'])? "green" : "red"?>"><?php echo ($plugin['result'])?'Installed':'Not installed'; ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
<?php
	}
	
	private function _renderPostUninstallation($status, $parent)
	{
?>
<?php $rows = 0;?>
<h2>Akeeba Release System uninstallation status</h2>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'Akeeba Release System '.JText::_('Component'); ?></td>
			<td><strong style="color: green"><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php echo JText::_('Module'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><strong style="color: <?php echo ($module['result'])? "green" : "red"?>"><?php echo ($module['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
		<?php if (count($status->plugins)) : ?>
		<tr>
			<th><?php echo JText::_('Plugin'); ?></th>
			<th><?php echo JText::_('Group'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><strong style="color: <?php echo ($plugin['result'])? "green" : "red"?>"><?php echo ($plugin['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
<?php
	}
	
	
	
	/**
	 * Joomla! 1.6+ bugfix for "DB function returned no error"
	 */
	private function _bugfixDBFunctionReturnedNoError()
	{
		$db = JFactory::getDbo();
			
		// Fix broken #__assets records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__assets')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->query();
		}

		// Fix broken #__extensions records
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__extensions')
				->where($db->qn('extension_id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->query();
		}

		// Fix broken #__menu records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->query();
		}
	}
	
	/**
	 * Joomla! 1.6+ bugfix for "Can not build admin menus"
	 */
	private function _bugfixCantBuildAdminMenus()
	{
		$db = JFactory::getDbo();
		
		// If there are multiple #__extensions record, keep one of them
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(count($ids) > 1) {
			asort($ids);
			$extension_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__extensions')
					->where($db->qn('extension_id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}
		}
				
		// If there are multiple assets records, delete all except the oldest one
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadObjectList();
		if(count($ids) > 1) {
			asort($ids);
			$asset_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__assets')
					->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// Remove #__menu records for good measure!
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		$ids1 = $db->loadColumn();
		if(empty($ids1)) $ids1 = array();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension.'&%'));
		$db->setQuery($query);
		$ids2 = $db->loadColumn();
		if(empty($ids2)) $ids2 = array();
		$ids = array_merge($ids1, $ids2);
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 * 
	 * @param JInstaller $parent 
	 * @return JObject The subextension installation status
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');
		
		$db = JFactory::getDbo();
		
		$status = new JObject();
		$status->modules = array();
		$status->plugins = array();
		
		// Modules installation
		if(count($this->installation_queue['modules'])) {
			foreach($this->installation_queue['modules'] as $folder => $modules) {
				if(count($modules)) foreach($modules as $module => $modulePreferences) {
					// Install the module
					if(empty($folder)) $folder = 'site';
					$path = "$src/modules/$folder/$module";
					if(!is_dir($path)) {
						$path = "$src/modules/$folder/mod_$module";
					}
					if(!is_dir($path)) {
						$path = "$src/modules/$module";
					}
					if(!is_dir($path)) {
						$path = "$src/modules/mod_$module";
					}
					if(!is_dir($path)) continue;
					// Was the module already installed?
					$sql = $db->getQuery(true)
						->select('COUNT(*)')
						->from('#__modules')
						->where($db->qn('module').' = '.$db->q('mod_'.$module));
					$db->setQuery($sql);
					$count = $db->loadResult();
					$installer = new JInstaller;
					$result = $installer->install($path);
					$status->modules[] = array(
						'name'=>'mod_'.$module,
						'client'=>$folder,
						'result'=>$result
					);
					// Modify where it's published and its published state
					if(!$count) {
						// A. Position and state
						list($modulePosition, $modulePublished) = $modulePreferences;
						if($modulePosition == 'cpanel') {
							$modulePosition = 'icon';
						}
						$sql = $db->getQuery(true)
							->update($db->qn('#__modules'))
							->set($db->qn('position').' = '.$db->q($modulePosition))
							->where($db->qn('module').' = '.$db->q('mod_'.$module));
						if($modulePublished) {
							$sql->set($db->qn('published').' = '.$db->q('1'));
						}
						$db->setQuery($sql);
						$db->query();
						
						// B. Change the ordering of back-end modules to 1 + max ordering
						if($folder == 'admin') {
							$query = $db->getQuery(true);
							$query->select('MAX('.$db->qn('ordering').')')
								->from($db->qn('#__modules'))
								->where($db->qn('position').'='.$db->q($modulePosition));
							$db->setQuery($query);
							$position = $db->loadResult();
							$position++;

							$query = $db->getQuery(true);
							$query->update($db->qn('#__modules'))
								->set($db->qn('ordering').' = '.$db->q($position))
								->where($db->qn('module').' = '.$db->q('mod_'.$module));
							$db->setQuery($query);
							$db->query();
						}
						
						// C. Link to all pages
						$query = $db->getQuery(true);
						$query->select('id')->from($db->qn('#__modules'))
							->where($db->qn('module').' = '.$db->q('mod_'.$module));
						$db->setQuery($query);
						$moduleid = $db->loadResult();

						$query = $db->getQuery(true);
						$query->select('*')->from($db->qn('#__modules_menu'))
							->where($db->qn('moduleid').' = '.$db->q($moduleid));
						$db->setQuery($query);
						$assignments = $db->loadObjectList();
						$isAssigned = !empty($assignments);
						if(!$isAssigned) {
							$o = (object)array(
								'moduleid'	=> $moduleid,
								'menuid'	=> 0
							);
							$db->insertObject('#__modules_menu', $o);
						}
					}
				}
			}
		}

		// Plugins installation
		if(count($this->installation_queue['plugins'])) {
			foreach($this->installation_queue['plugins'] as $folder => $plugins) {
				if(count($plugins)) foreach($plugins as $plugin => $published) {
					$path = "$src/plugins/$folder/$plugin";
					if(!is_dir($path)) {
						$path = "$src/plugins/$folder/plg_$plugin";
					}
					if(!is_dir($path)) {
						$path = "$src/plugins/$plugin";
					}
					if(!is_dir($path)) {
						$path = "$src/plugins/plg_$plugin";
					}
					if(!is_dir($path)) continue;

					// Was the plugin already installed?
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where($db->qn('element').' = '.$db->q($plugin))
						->where($db->qn('folder').' = '.$db->q($folder));
					$db->setQuery($query);
					$count = $db->loadResult();

					$installer = new JInstaller;
					$result = $installer->install($path);
					
					$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);

					if($published && !$count) {
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled').' = '.$db->q('1'))
							->where($db->qn('element').' = '.$db->q($plugin))
							->where($db->qn('folder').' = '.$db->q($folder));
						$db->setQuery($query);
						$db->query();
					}
				}
			}
		}
		
		return $status;
	}
	
	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 * 
	 * @param JInstaller $parent 
	 * @return JObject The subextension uninstallation status
	 */
	private function _uninstallSubextensions($parent)
	{
		jimport('joomla.installer.installer');
		
		$db = JFactory::getDBO();
		
		$status = new JObject();
		$status->modules = array();
		$status->plugins = array();
		
		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if(count($this->installation_queue['modules'])) {
			foreach($this->installation_queue['modules'] as $folder => $modules) {
				if(count($modules)) foreach($modules as $module => $modulePreferences) {
					// Find the module ID
					$sql = $db->getQuery(true)
						->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('element').' = '.$db->q('mod_'.$module))
						->where($db->qn('type').' = '.$db->q('module'));
					$db->setQuery($sql);
					$id = $db->loadResult();
					// Uninstall the module
					if($id) {
						$installer = new JInstaller;
						$result = $installer->uninstall('module',$id,1);
						$status->modules[] = array(
							'name'=>'mod_'.$module,
							'client'=>$folder,
							'result'=>$result
						);
					}
				}
			}
		}

		// Plugins uninstallation
		if(count($this->installation_queue['plugins'])) {
			foreach($this->installation_queue['plugins'] as $folder => $plugins) {
				if(count($plugins)) foreach($plugins as $plugin => $published) {
					$sql = $db->getQuery(true)
						->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type').' = '.$db->q('plugin'))
						->where($db->qn('element').' = '.$db->q($plugin))
						->where($db->qn('folder').' = '.$db->q($folder));
					$db->setQuery($sql);

					$id = $db->loadResult();
					if($id)
					{
						$installer = new JInstaller;
						$result = $installer->uninstall('plugin',$id,1);
						$status->plugins[] = array(
							'name'=>'plg_'.$plugin,
							'group'=>$folder,
							'result'=>$result
						);
					}			
				}
			}
		}
		
		return $status;
	}
	
	/**
	 * Removes obsolete files and folders
	 * 
	 * @param array $akeebaRemoveFiles 
	 */
	private function _removeObsoleteFilesAndFolders($akeebaRemoveFiles)
	{
		// Remove files
		jimport('joomla.filesystem.file');
		if(!empty($akeebaRemoveFiles['files'])) foreach($akeebaRemoveFiles['files'] as $file) {
			$f = JPATH_ROOT.'/'.$file;
			if(!JFile::exists($f)) continue;
			JFile::delete($f);
		}

		// Remove folders
		jimport('joomla.filesystem.file');
		if(!empty($akeebaRemoveFiles['folders'])) foreach($akeebaRemoveFiles['folders'] as $folder) {
			$f = JPATH_ROOT.'/'.$folder;
			if(!JFolder::exists($f)) continue;
			JFolder::delete($f);
		}
	}
	
	private function _installFOF($parent)
	{
		$src = $parent->getParent()->getPath('source');
		
		// Install the FOF framework
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		jimport('joomla.utilities.date');
		$source = $src.'/fof';
		if(!defined('JPATH_LIBRARIES')) {
			$target = JPATH_ROOT.'/libraries/fof';
		} else {
			$target = JPATH_LIBRARIES.'/fof';
		}
		$haveToInstallFOF = false;
		if(!JFolder::exists($target)) {
			$haveToInstallFOF = true;
		} else {
			$fofVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$fofVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$fofVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);

			$haveToInstallFOF = $fofVersion['package']['date']->toUNIX() > $fofVersion['installed']['date']->toUNIX();
		}

		$installedFOF = false;
		if($haveToInstallFOF) {
			$versionSource = 'package';
			$installer = new JInstaller;
			$installedFOF = $installer->install($source);
		} else {
			$versionSource = 'installed';
		}
		
		if(!isset($fofVersion)) {
			$fofVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$fofVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$fofVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);
			$versionSource = 'installed';
		}
		
		if(!($fofVersion[$versionSource]['date'] instanceof JDate)) {
			$fofVersion[$versionSource]['date'] = new JDate();
		}
		
		return array(
			'required'	=> $haveToInstallFOF,
			'installed'	=> $installedFOF,
			'version'	=> $fofVersion[$versionSource]['version'],
			'date'		=> $fofVersion[$versionSource]['date']->format('Y-m-d'),
		);
	}
}