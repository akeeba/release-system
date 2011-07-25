<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.html.pane');
$tabs	= JPane::getInstance('tabs');

$lang =& JFactory::getLanguage();
$icons_root = JURI::base().'components/com_ars/assets/images/';

$groups = array('basic','tools','update');
?>
<div id="cpanel">
	<div class="ak_cpanel_modules" id="ak_cpanel_modules">

		<fieldset>
			<legend><?php echo JText::_('LBL_ARS_CPANEL_DLSTATSMONTHLY')?></legend>
			<div id="mdrChart"></div>
		</fieldset>
		
		<fieldset>
			<legend><?php echo JText::_('LBL_ARS_CPANEL_DLSTATSDETAILS')?></legend>
			<table border="0" width="100%" class="dlstats">
				<tr>
					<td class="dlstats-label"><?php echo JText::_('LBL_ARS_CPANEL_DL_EVER') ?></td>
					<td><?php echo number_format($this->dlever,0) ?></td>
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
		</fieldset>
		
		<fieldset>
			<legend><?php echo JText::_('LBL_ARS_CPANEL_POPULAR_WEEK')?></legend>
			
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
		</fieldset>
		
	</div>

	<div class="ak_cpanel_main_container">
	<?php foreach($groups as $group): ?>
		<?php if(array_key_exists($group, $this->icondefs)): ?>
		<?php if(!count($this->icondefs[$group])) continue; ?>
		
		<fieldset class="ak_cpanel_icons">
			<legend><?php echo JText::_('LBL_ARS_CPANEL_'.  strtoupper($group)); ?></legend>
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
			
			<?php if($group == 'basic'): ?>
			<p><?php echo LiveUpdate::getIcon(); ?></p>
			<?php endif; ?>
			<!-- <div class="ak_clr_left"></div>  -->
		</fieldset>
		
		<?php endif; ?>
	<?php endforeach; ?>
	
	</div>
</div>

<div class="ak_clr"></div>

<p>
	<strong><?php echo JText::sprintf('ARS_COPYRIGHT', date('Y')); ?></strong><br/>
	<?php echo JText::_('ARS_LICENSE'); ?>
</p>

<?php
	$mdrMin = min(array_values($this->mdreport));
	$mdrMax = max(array_values($this->mdreport));
	$mdrSpread = $mdrMax - $mdrMin;
	if($mdrSpread < 10) {
		if($mdrMax < 10) $mdrMax = 10;
		$mdrMin = $mdrMax - 10;
	} else {
		if($mdrMin > (int)($mdrSpread/10)) {
			$mdrMin -= (int)($mdrSpread/10);
		}
	}
	$mdrSerie1 = implode(',',array_values($this->mdreport));
?>

<script type="text/javascript">
akeeba.jQuery(document).ready(function($){
	$.jqplot.config.enablePlugins = true;
	var dlPoints = [];
	<?php foreach ($this->mdreport as $mdDate => $mdDls): ?>
	dlPoints.push(['<?echo $mdDate?>', parseInt('<?php echo $mdDls?>' * 100) * 1 / 100]);
	<?php endforeach; ?>
		
	plot1 = $.jqplot('mdrChart', [dlPoints], {
		show: true,
		axes:{
			xaxis:{
				renderer: $.jqplot.DateAxisRenderer,
				tickInterval:'1 week'
			},
			yaxis:{min: 0,tickOptions:{formatString:'%u'}}
		},
		series:[ 
			{
				lineWidth:3,
				markerOptions:{
					style:'filledCircle',
					size:8
				},
				renderer: $.jqplot.hermiteSplineRenderer,
				rendererOptions:{steps: 60, tension: 0.6}
			}
		],
		highlighter: {sizeAdjust: 7.5},
		axesDefaults:{useSeriesColor: true}
	});
	
	/**
	$('#mdrChart').gchart('destroy').
		gchart({
			usePost: false,
			type: 'line',
			legend: null,
			dataLabels: {},
			series: [$.gchart.series('DL',[<?php echo $mdrSerie1 ?>],'blue', <?php echo $mdrMin?>, <?php echo $mdrMax?>)],
			axes: [
				$.gchart.axis('left', <?php echo $mdrMin?>, <?php echo $mdrMax?>)
			] 
		});
	/**/
});
</script>