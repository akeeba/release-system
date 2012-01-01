<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ars.php 123 2011-04-13 07:47:16Z nikosdion $
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

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
$ctableAssoc = $db->loadResultArray(1);
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
$ctableAssoc = $db->loadResultArray(1);
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
$ctableAssoc = $db->loadResultArray(1);
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
$ctableAssoc = $db->loadResultArray(1);
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
$ctableAssoc = $db->loadResultArray(1);
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`ars_log_userid`')) {
	$db->setQuery('CREATE INDEX `ars_log_userid` ON `#__ars_log` (`user_id`)');
	$db->query();
}

// Schema updates -- END

// Install modules and plugins -- BEGIN

// -- General settings
jimport('joomla.installer.installer');
$db = & JFactory::getDBO();
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

// -- Download ID
if(is_dir($src.'/mod_arsdlid')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/mod_arsdlid');
	$status->modules[] = array('name'=>'mod_arsdlid','client'=>'site', 'result'=>$result);
}

// -- My Downloads
if(is_dir($src.'/mod_arsdownloads')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/mod_arsdownloads');
	$status->modules[] = array('name'=>'mod_arsdownloads','client'=>'site', 'result'=>$result);
}

// -- Plugin: plg_bleedingedgematurity
if(is_dir($src.'/plg_bleedingedgematurity')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/plg_bleedingedgematurity');
	$status->plugins[] = array('name'=>'plg_bleedingedgematurity','group'=>'ars', 'result'=>$result);
}

// -- Plugin: plg_bleedingedgediff
if(is_dir($src.'/plg_bleedingedgediff')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/plg_bleedingedgediff');
	$status->plugins[] = array('name'=>'plg_bleedingedgediff','group'=>'ars', 'result'=>$result);
}

// -- Plugin: plg_arsdlid
if(is_dir($src.'/plg_arsdlid')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/plg_arsdlid');
	$status->plugins[] = array('name'=>'plg_arsdlid','group'=>'content', 'result'=>$result);
}

// -- Plugin: plg_arslink
if(is_dir($src.'/plg_arslink')) {
	$installer = new JInstaller;
	$result = $installer->install($src.'/plg_arslink');
	$status->plugins[] = array('name'=>'plg_arslink','group'=>'editors-xtd', 'result'=>$result);
}

// Install modules and plugins -- END

// Load the translation strings (Joomla! 1.5 and 1.6 compatible)
if( version_compare( JVERSION, '1.6.0', 'lt' ) ) {
	global $j15;
	// Joomla! 1.5 will have to load the translation strings
	$j15 = true;
	$jlang =& JFactory::getLanguage();
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