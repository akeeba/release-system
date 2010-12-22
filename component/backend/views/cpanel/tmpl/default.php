<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

$lang =& JFactory::getLanguage();
$icons_root = JURI::base().'components/com_ars/assets/images/';

$groups = array('basic','insight','tools','update');
?>
<div id="cpanel">
	<div class="ak_cpanel_modules" id="ak_cpanel_modules">

		<h3><?php echo JText::_('LBL_ARS_CPANEL_DLSTATS') ?></h3>
		<div class="ak_cpanel_status_cell">
			<table border="0" width="100%" class="dlstats">
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_EVER') ?></td>
					<td><?php echo number_format($this->dlever,0) ?></td>
				</tr>
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_YEAR') ?></td>
					<td><?php echo number_format($this->dlyear,0) ?></td>
				</tr>
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_LMONTH') ?></td>
					<td><?php echo number_format($this->dllastmonth,0) ?></td>
				</tr>
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_MONTH') ?></td>
					<td><?php echo number_format($this->dlmonth,0) ?></td>
				</tr>
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_WEEK') ?></td>
					<td><?php echo number_format($this->dlweek,0) ?></td>
				</tr>
			</table>
			<div id="mapChartMini"></div>
		</div>

		<h3><?php echo JText::_('LBL_ARS_CPANEL_POPULAR') ?> &ndash; <?php echo JText::_('LBL_ARS_CPANEL_POPULAR_WEEK') ?></h3>
		<div class="ak_cpanel_status_cell">
		<?php if(empty($this->popularweek)): ?>
			<p><?php echo JText::_('LBL_ARS_NOITEMS') ?></p>
		<?php else: ?>
			<?php foreach ($this->popularweek as $item): ?>
			<div class="dlpopular">
				<div class="dlbasic">
					<a class="dltitle" href="../index.php?option=com_ars&view=download&id=<?php echo (int)$item->item_id ?>">
						<?php echo $this->escape($item->title) ?>
					</a>
					<span class="dltimes"><?php echo $this->escape($item->dl) ?></span>
				</div>
				<div class="dladvanced">
					<span class="dlcategory"><?php echo $this->escape($item->category) ?></span>
					<span class="dlversion"><?php echo $this->escape($item->version) ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>

		<h3><?php echo JText::_('LBL_ARS_CPANEL_POPULAR') ?> &ndash; <?php echo JText::_('LBL_ARS_CPANEL_POPULAR_EVER') ?></h3>
		<div class="ak_cpanel_status_cell">
		<?php if(empty($this->popularever)): ?>
			<p><?php echo JText::_('LBL_ARS_NOITEMS') ?></p>
		<?php else: ?>
			<?php foreach ($this->popularever as $item): ?>
			<div class="dlpopular">
				<div class="dlbasic">
					<a class="dltitle" href="../index.php?option=com_ars&view=download&id=<?php echo (int)$item->item_id ?>">
						<?php echo $this->escape($item->title) ?>
					</a>
					<span class="dltimes"><?php echo $this->escape($item->dl) ?></span>
				</div>
				<div class="dladvanced">
					<span class="dlcategory"><?php echo $this->escape($item->category) ?></span>
					<span class="dlversion"><?php echo $this->escape($item->version) ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>

	</div>

	<div class="ak_cpanel_main_container">
	<?php foreach($groups as $group): ?>
		<?php if(array_key_exists($group, $this->icondefs)): ?>
		<?php if(!count($this->icondefs[$group])) continue; ?>
		<div class="ak_cpanel_header ui-widget-header ui-corner-tl ui-corner-tr">
			<?php echo JText::_('LBL_ARS_CPANEL_'.  strtoupper($group)); ?>
		</div>
		<div class="ak_cpanel_icons ui-widget-content ui-corner-br ui-corner-bl">
			<?php foreach($this->icondefs[$group] as $icon): ?>
			<div class="icon">
				<a href="<?php echo 'index.php?option=com_ars'.
					(is_null($icon['view']) ? '' : '&amp;view='.$icon['view']).
					(is_null($icon['task']) ? '' : '&amp;task='.$icon['task']); ?>">
				<div class="ak-icon ak-icon-<?php echo $icon['icon'] ?>">&nbsp;</div>
				<span><?php echo $icon['label']; ?></span>
				</a>
			</div>
			<?php endforeach; ?>
			<div class="ak_clr_left"></div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>
	</div>
</div>

<div class="ak_clr"></div>

<p>
	<?php echo JText::sprintf('ARS_COPYRIGHT', date('y')); ?><br/>
	<?php echo JText::_('ARS_LICENSE'); ?>
</p>

<script type="text/javascript">
akeeba.jQuery(document).ready(function($){
	$('#ak_cpanel_modules').accordion();
	
	var areaData = <?php echo json_encode((object)$this->countrystats) ?>;
	$('#mapChartMini').gchart('destroy').
		gchart( $.gchart.map('world', areaData, 'white', 'aaffaa', 'green') )

});
</script>
