<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: ars.php 123 2011-04-13 07:47:16Z nikosdion $
 */

// no direct access
defined('_JEXEC') or die('');

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

jimport('joomla.installer.installer');
$db = JFactory::getDBO();
$status = new JObject();
$status->modules = array();
$status->plugins = array();
$src = $this->parent->getPath('source');

// Modules uninstallation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
		if(count($modules)) foreach($modules as $module => $modulePreferences) {
			// Find the module ID
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = '.$db->Quote('mod_'.$module).' AND `type` = "module"');
			} else {
				$db->setQuery('SELECT `id` FROM #__modules WHERE `module` = '.$db->Quote('mod_'.$module));
			}
			$id = $db->loadResult();
			// Uninstall the module
			$installer = new JInstaller;
			$result = $installer->uninstall('module',$id,1);
			$status->modules[] = array('name'=>'mod_'.$module,'client'=>$folder, 'result'=>$result);
		}
	}
}

// Plugins uninstallation
if(count($installation_queue['plugins'])) {
	foreach($installation_queue['plugins'] as $folder => $plugins) {
		if(count($plugins)) foreach($plugins as $plugin => $published) {
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = '.$db->Quote($plugin).' AND `folder` = '.$db->Quote($folder));
			} else {
				$db->setQuery('SELECT `id` FROM #__plugins WHERE `element` = '.$db->Quote($plugin).' AND `folder` = '.$db->Quote($folder));
			}
			
			$id = $db->loadResult();
			if($id)
			{
				$installer = new JInstaller;
				$result = $installer->uninstall('plugin',$id,1);
				$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);
			}			
		}
	}
}

?>

<?php $rows = 0;?>
<h2><?php echo JText::_('Akeeba Release System Uninstallation Status'); ?></h2>
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
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
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
			<td><strong><?php echo ($module['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
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
			<td><strong><?php echo ($plugin['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>