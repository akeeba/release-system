<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var ArsViewCpanels $this */

$lang = JFactory::getLanguage();
$icons_root = JURI::base() . 'components/com_ars/assets/images/';

$groups = array('basic', 'tools', 'update');

JHtml::_('behavior.core');
JHtml::_('formbehavior.chosen', 'select');

F0FTemplateUtils::addCSS('media://com_ars/css/backend.css');

F0FTemplateUtils::addJS('media://com_ars/js/gui-helpers.js');
F0FTemplateUtils::addJS('media://com_ars/js/jquery.jqplot.min.js');
F0FTemplateUtils::addJS('media://com_ars/js/jqplot.dateAxisRenderer.min.js');
F0FTemplateUtils::addJS('media://com_ars/js/jqplot.hermite.js');
F0FTemplateUtils::addJS('media://com_ars/js/jqplot.highlighter.min.js');
F0FTemplateUtils::addJS('media://com_ars/js/jquery.colorhelpers.min.js');

?>

<?php
// Obsolete PHP version check
if (version_compare(PHP_VERSION, '5.4.0', 'lt')):
	JLoader::import('joomla.utilities.date');
	$akeebaCommonDatePHP = new JDate('2014-08-14 00:00:00', 'GMT');
	$akeebaCommonDateObsolescence = new JDate('2015-05-14 00:00:00', 'GMT');
	?>
	<div id="phpVersionCheck" class="alert alert-warning">
		<h3><?php echo JText::_('AKEEBA_COMMON_PHPVERSIONTOOOLD_WARNING_TITLE'); ?></h3>
		<p>
			<?php echo JText::sprintf(
				'AKEEBA_COMMON_PHPVERSIONTOOOLD_WARNING_BODY',
				PHP_VERSION,
				$akeebaCommonDatePHP->format(JText::_('DATE_FORMAT_LC1')),
				$akeebaCommonDateObsolescence->format(JText::_('DATE_FORMAT_LC1')),
				'5.5'
			);
			?>
		</p>
	</div>
<?php endif; ?>

<div id="updateNotice"></div>

<?php if (!$this->hasplugin): ?>
	<div class="well">
		<h3><?php echo JText::_('COM_ARS_GEOBLOCK_LBL_GEOIPPLUGINSTATUS') ?></h3>

		<p><?php echo JText::_('COM_ARS_GEOBLOCK_LBL_GEOIPPLUGINMISSING') ?></p>

		<a class="btn btn-primary" href="https://www.akeebabackup.com/download/akgeoip.html" target="_blank">
			<span class="icon icon-white icon-download-alt"></span>
			<?php echo JText::_('COM_ARS_GEOBLOCK_LBL_DOWNLOADGEOIPPLUGIN') ?>
		</a>
	</div>
<?php elseif ($this->pluginNeedsUpdate): ?>
	<div class="well well-small">
		<h3><?php echo JText::_('COM_ARS_GEOBLOCK_LBL_GEOIPPLUGINEXISTS') ?></h3>

		<p><?php echo JText::_('COM_ARS_GEOBLOCK_LBL_GEOIPPLUGINCANUPDATE') ?></p>

		<a class="btn btn-small"
		   href="index.php?option=com_ars&view=cpanel&task=updategeoip&<?php echo JFactory::getSession()
																						  ->getFormToken(); ?>=1">
			<span class="icon icon-retweet"></span>
			<?php echo JText::_('COM_ARS_GEOBLOCK_LBL_UPDATEGEOIPDATABASE') ?>
		</a>
	</div>
<?php endif; ?>


<div class="row-fluid">
	<div id="cpanel" class="span<?php echo $this->graphswidth ?>">

		<h3><?php echo JText::_('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL') ?></h3>

		<div id="mdrChart"></div>

		<h3><?php echo JText::_('COM_ARS_CPANEL_DLSTATSDETAILS_LABEL') ?></h3>
		<table border="0" width="100%" class="table table-striped">
			<tr>
				<td class="dlstats-label"><?php echo JText::_('COM_ARS_CPANEL_DL_EVER_LABEL') ?></td>
				<td><?php echo number_format($this->dlever, 0) ?></td>
			</tr>
			<tr>
				<td class="dlstats-label"><?php echo JText::_('COM_ARS_CPANEL_DL_THISMONTH_LABEL') ?></td>
				<td><?php echo number_format($this->dlmonth, 0) ?></td>
			</tr>
			<tr>
				<td class="dlstats-label"><?php echo JText::_('COM_ARS_CPANEL_DL_THISWEEK_LABEL') ?></td>
				<td><?php echo number_format($this->dlweek, 0) ?></td>
			</tr>
		</table>

		<div style="clear: both;">&nbsp;</div>
		<h3><?php echo JText::_('COM_ARS_CPANEL_POPULAR_WEEK_LABEL') ?></h3>

		<?php if (empty($this->popularweek)): ?>
			<p><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></p>
		<?php else: ?>
			<?php foreach ($this->popularweek as $item): ?>
				<div class="dlpopular">
					<div class="dlbasic">
						<a class="dltitle"
						   href="../index.php?option=com_ars&view=download&id=<?php echo (int)$item->item_id ?>">
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

		<div style="clear: both;">&nbsp;</div>
		<h3><?php echo JText::_('COM_ARS_CPANEL_VERSIONINFO_LABEL') ?></h3>

		<p>
			Akeeba Release System <?php echo $this->currentVersion ?>

			<a href="index.php?option=com_ars&view=update&task=force" class="btn btn-inverse btn-small">
				<?php echo JText::_('COM_ARS_CPANEL_MSG_RELOADUPDATE'); ?>
			</a>

		</p>

	</div>

	<div id="cpanel" class="span<?php echo 12 - $this->graphswidth ?>">
		<?php foreach ($groups as $group): ?>
			<?php if (array_key_exists($group, $this->icondefs)): ?>
				<?php if (!count($this->icondefs[$group]))
				{
					continue;
				} ?>

				<h3><?php echo JText::_('LBL_ARS_CPANEL_' . strtoupper($group)); ?></h3>
				<?php foreach ($this->icondefs[$group] as $icon): ?>
					<div class="icon">
						<a href="<?php echo 'index.php?option=com_ars' .
							(is_null($icon['view']) ? '' : '&amp;view=' . $icon['view']) .
							(is_null($icon['task']) ? '' : '&amp;task=' . $icon['task']); ?>">
							<div class="ak-icon ak-icon-<?php echo $icon['icon'] ?>">&nbsp;</div>
							<span><?php echo $icon['label']; ?></span>
						</a>
					</div>
				<?php endforeach; ?>

				<div class="ak_clr_left"></div>

			<?php endif; ?>
		<?php endforeach; ?>

	</div>
</div>

<div class="ak_clr"></div>

<div class="row-fluid footer">
	<div class="span12">
		<p style="font-size: small" class="well">
			<strong><?php echo JText::sprintf('COM_ARS_CPANEL_COPYRIGHT_LABEL', date('Y')); ?></strong><br/>
			<?php echo JText::_('COM_ARS_CPANEL_LICENSE_LABEL'); ?><br/>
			<strong>
				If you use Akeeba Release System, please post a rating and a review at the
				<a href="http://extensions.joomla.org/extensions/directory-a-documentation/downloads/16825">Joomla!
					Extensions Directory</a>.
			</strong>
		</p>
	</div>
</div>

<?php
$mdrMin = min(array_values($this->mdreport));
$mdrMax = max(array_values($this->mdreport));
$mdrSpread = $mdrMax - $mdrMin;
if ($mdrSpread < 10)
{
	if ($mdrMax < 10) $mdrMax = 10;
	$mdrMin = $mdrMax - 10;
}
else
{
	if ($mdrMin > (int)($mdrSpread / 10))
	{
		$mdrMin -= (int)($mdrSpread / 10);
	}
}
$mdrSerie1 = implode(',', array_values($this->mdreport));

if($this->statsIframe)
{
    echo $this->statsIframe;
}

?>

<script type="text/javascript">
	akeeba.jQuery(document).ready(function ($)
	{
		$.jqplot.config.enablePlugins = true;
		var dlPoints = [];
		<?php foreach ($this->mdreport as $mdDate => $mdDls): ?>
		dlPoints.push(['<?php echo $mdDate?>', parseInt('<?php echo $mdDls?>' * 100) * 1 / 100]);
		<?php endforeach; ?>

		plot1 = $.jqplot('mdrChart', [dlPoints], {
			show:         true,
			axes:         {
				xaxis: {
					renderer:     $.jqplot.DateAxisRenderer,
					tickInterval: '1 week'
				},
				yaxis: {min: 0, tickOptions: {formatString: '%u'}}
			},
			series:       [
				{
					lineWidth:       3,
					markerOptions:   {
						style: 'filledCircle',
						size:  8
					},
					renderer:        $.jqplot.hermiteSplineRenderer,
					rendererOptions: {steps: 60, tension: 0.6}
				}
			],
			highlighter:  {sizeAdjust: 7.5},
			axesDefaults: {useSeriesColor: true}
		});

		$.ajax('index.php?option=com_ars&view=cpanel&task=updateinfo&tmpl=component', {
			success: function (msg, textStatus, jqXHR)
			{
				// Get rid of junk before and after data
				var match = msg.match(/###([\s\S]*?)###/);
				data = match[1];

				if (data.length)
				{
					$('#updateNotice').html(data);
				}
			}
		});
	});
</script>