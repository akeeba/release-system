<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ars.php 123 2011-04-13 07:47:16Z nikosdion $
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// =============================================================================
// Akeeba Component Installation Configuration
// =============================================================================
$installation_queue = array(
	// modules => { (folder) => { (module) => { (position), (published) } }* }*
	'modules' => array(
		'admin' => array(
		),
		'site' => array(
			'arsdlid'		=> array('left', 0),
			'arsdownloads'	=> array('left', 0),
		)
	),
	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
		'ars' => array(
			'bleedingedgematurity'	=> 0,
			'bleedingedgediff'		=> 0,
		),
		'content' => array(
			'arsdlid'				=> 1,
		),
		'editors-xtd' => array(
			'arslink'				=> 1,
		),
	)
);

// Joomla! 1.6 Beta 13+ hack
if( version_compare( JVERSION, '1.6.0', 'ge' ) && !defined('_AKEEBA_HACK') ) {
	return;
} else {
	global $akeeba_installation_has_run;
	if($akeeba_installation_has_run) return;
}

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

// Schema updates -- BEGIN
$db = JFactory::getDBO();

// --- Update to 1.0.1
$sql = 'SHOW CREATE TABLE `#__ars_log`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`ars_log_accessed`')) {
	$db->setQuery('CREATE INDEX `ars_log_accessed` ON `#__ars_log` (`accessed_on`)');
	$db->query();

	$db->setQuery('CREATE INDEX `ars_log_authorized` ON `#__ars_log` (`authorized`)');
	$db->query();

	$db->setQuery('CREATE INDEX `ars_log_itemid` ON `#__ars_log` (`item_id`)');
	$db->query();
}

// Update to 1.0.2 - Part I: Language fields
$sql = 'SHOW CREATE TABLE `#__ars_categories`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`language`'))
{
	$sql = "ALTER TABLE `#__ars_categories` ADD COLUMN `language` char(7) NOT NULL DEFAULT '*' AFTER `published`";
	$db->setQuery($sql);
	$status = $db->query();
	
	$sql = "ALTER TABLE `#__ars_releases` ADD COLUMN `language` char(7) NOT NULL DEFAULT '*' AFTER `published`";
	$db->setQuery($sql);
	$status = $db->query();
	
	$sql = "ALTER TABLE `#__ars_items` ADD COLUMN `language` char(7) NOT NULL DEFAULT '*' AFTER `published`";
	$db->setQuery($sql);
	$status = $db->query();
}

// Update to 1.0.2 - Part II: Visual groups
$sql = 'SHOW CREATE TABLE `#__ars_categories`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`vgroup_id`'))
{
	$sql = "ALTER TABLE `#__ars_categories` ADD COLUMN `vgroup_id` bigint(20) NOT NULL DEFAULT '0' AFTER `directory`";
	$db->setQuery($sql);
	$status = $db->query();
}

// Update to 1.0.3 - client_id and folder in #__updatestreams
$sql = 'SHOW CREATE TABLE `#__ars_updatestreams`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`client_id`'))
{
	$sql = "ALTER TABLE `#__ars_updatestreams` ADD COLUMN `folder` varchar(255) DEFAULT '' AFTER `packname`";
	$db->setQuery($sql);
	$status = $db->query();

	$sql = "ALTER TABLE `#__ars_updatestreams` ADD COLUMN `client_id` int(1) NOT NULL DEFAULT '1' AFTER `packname`";
	$db->setQuery($sql);
	$status = $db->query();
}

// --- Update to 1.0.4
$sql = 'SHOW CREATE TABLE `#__ars_log`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`ars_log_userid`')) {
	$db->setQuery('CREATE INDEX `ars_log_userid` ON `#__ars_log` (`user_id`)');
	$db->query();
}

// --- Update to 1.1
$sql = 'SHOW CREATE TABLE `#__ars_items`';
$db->setQuery($sql);
if(version_compare(JVERSION, '3.0', 'ge')) {
	$ctableAssoc = $db->loadColumn(1);
} else {
	$ctableAssoc = $db->loadResultArray(1);
}
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`environments`')) {
	$sql = "ALTER TABLE `#__ars_items` ADD COLUMN `environments` varchar(100) DEFAULT NULL AFTER `language`";
	$db->setQuery($sql);
	$status = $db->query();

	$sql = "ALTER TABLE `#__ars_autoitemdesc` ADD COLUMN `environments` varchar(100) DEFAULT NULL AFTER `description`";
	$db->setQuery($sql);
	$status = $db->query();
}

// --- Sample records for the Environments feature
$sql = 'SELECT COUNT(*) FROM `#__ars_environments`';
$db->setQuery($sql);
$num = $db->loadResult();
if($num < 1) {
	$samples = array(
		(object)array(
			'title'		=> 'Joomla! 1.5',
			'xmltitle'	=> 'joomla/1.5',
			'icon'		=> 'ars-joomla15.png'
		),
		(object)array(
			'title'		=> 'Joomla! 1.6',
			'xmltitle'	=> 'joomla/1.6',
			'icon'		=> 'ars-joomla16.png'
		),
		(object)array(
			'title'		=> 'Joomla! 1.7',
			'xmltitle'	=> 'joomla/1.7',
			'icon'		=> 'ars-joomla17.png'
		),
		(object)array(
			'title'		=> 'Joomla! 2.5',
			'xmltitle'	=> 'joomla/2.5',
			'icon'		=> 'ars-joomla25.png'
		),
		(object)array(
			'title'		=> 'WHMCS 4.5+',
			'xmltitle'	=> 'whmcs/4.5',
			'icon'		=> 'ars-whmcs452.png'
		),
		(object)array(
			'title'		=> 'WordPress 3.2',
			'xmltitle'	=> 'wordpress/3.2',
			'icon'		=> 'ars-wp.png'
		),
		(object)array(
			'title'		=> 'Windows XP',
			'xmltitle'	=> 'windows/xp',
			'icon'		=> 'ars-winxp.png'
		),
		(object)array(
			'title'		=> 'Windows 7',
			'xmltitle'	=> 'windows/7',
			'icon'		=> 'ars-win7.png'
		),
		(object)array(
			'title'		=> 'Mac OS X',
			'xmltitle'	=> 'macosx/10.6',
			'icon'		=> 'ars-macosx.png'
		),
		(object)array(
			'title'		=> 'Linux 32-bit',
			'xmltitle'	=> 'linux/i386',
			'icon'		=> 'ars-linux32.png'
		),
		(object)array(
			'title'		=> 'Linux 64-bit',
			'xmltitle'	=> 'linux/x86-64',
			'icon'		=> 'ars-linux64.png'
		),
	);
	foreach($samples as $sample) {
		$db->insertObject('#__ars_environments', $sample);
	}
}

// Schema updates -- END

// Install modules and plugins -- BEGIN

// -- General settings
jimport('joomla.installer.installer');
$db = JFactory::getDBO();
$status = new JObject();
$status->modules = array();
$status->plugins = array();
if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
	// Thank you for removing installer features in Joomla! 1.6 Beta 13 and
	// forcing me to write ugly code, Joomla!...
	$src = dirname(__FILE__);
} else {
	$src = $this->parent->getPath('source');
}

// Modules installation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
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
			$sql = 'SELECT COUNT(*) FROM #__modules WHERE `module`='.$db->Quote('mod_'.$module);
			$db->setQuery($sql);
			$count = $db->loadResult();
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->modules[] = array('name'=>'mod_'.$module, 'client'=>$folder, 'result'=>$result);
			// Modify where it's published and its published state
			if(!$count) {
				// A. Position and state
				list($modulePosition, $modulePublished) = $modulePreferences;
				if(version_compare(JVERSION, '2.5.0', 'ge') && ($modulePosition == 'cpanel')) {
					$modulePosition = 'icon';
				}
				$sql = "UPDATE #__modules SET position=".$db->Quote($modulePosition);
				if($modulePublished) $sql .= ', published=1';
				$sql .= ' WHERE `module`='.$db->Quote('mod_'.$module);
				$db->setQuery($sql);
				$db->query();
				if(version_compare(JVERSION, '1.7.0', 'ge')) {
					// B. Change the ordering of back-end modules to 1 + max ordering in J! 1.7+
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
					// C. Link to all pages on Joomla! 1.7+
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
}

// Plugins installation
if(count($installation_queue['plugins'])) {
	foreach($installation_queue['plugins'] as $folder => $plugins) {
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
			if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
				$query = "SELECT COUNT(*) FROM  #__extensions WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
			} else {
				$query = "SELECT COUNT(*) FROM  #__plugins WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
			}
			$db->setQuery($query);
			$count = $db->loadResult();
			
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);
			
			if($published && !$count) {
				if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
					$query = "UPDATE #__extensions SET enabled=1 WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
				} else {
					$query = "UPDATE #__plugins SET published=1 WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
				}
				$db->setQuery($query);
				$db->query();
			}
		}
	}
}

// Install modules and plugins -- END

// Load the translation strings (Joomla! 1.5 and 1.6 compatible)
if( version_compare( JVERSION, '1.6.0', 'lt' ) ) {
	global $j15;
	// Joomla! 1.5 will have to load the translation strings
	$j15 = true;
	$jlang = JFactory::getLanguage();
	$path = JPATH_ADMINISTRATOR.'/components/com_ars';
	$jlang->load('com_ars.sys', $path, 'en-GB', true);
	$jlang->load('com_ars.sys', $path, $jlang->getDefault(), true);
	$jlang->load('com_ars.sys', $path, null, true);
} else {
	$j15 = false;
}

if(!function_exists('pitext'))
{
	function pitext($key)
	{
		global $j15;
		$string = JText::_($key);
		if($j15)
		{
			$string = str_replace('"_QQ_"', '"', $string);
		}
		echo $string;
	}
}

if(!function_exists('pisprint'))
{
	function pisprint($key, $param)
	{
		global $j15;
		$string = JText::sprintf($key, $param);
		if($j15)
		{
			$string = str_replace('"_QQ_"', '"', $string);
		}
		echo $string;
	}
}

// Finally, show the installation results form
?>
<h1><?php pitext('COM_ARS_PIHEADER'); ?></h1>

<?php $rows = 0;?>
<img src="../media/com_ars/icons/ars_logo_48.png" width="48" height="48" alt="Akeeba Release System" align="right" />

<h2><?php pitext('COM_ARS_PIWELCOME') ?></h2>
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
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
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
			<td><strong><?php echo ($module['result'])?JText::_('Installed'):JText::_('Not installed'); ?></strong></td>
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
			<td><strong><?php echo ($plugin['result'])?JText::_('Installed'):JText::_('Not installed'); ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>

<?php
global $akeeba_installation_has_run;
$akeeba_installation_has_run = 1;
?>