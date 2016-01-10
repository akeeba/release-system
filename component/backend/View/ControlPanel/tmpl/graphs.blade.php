<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

$pointsJavascript = '';

foreach ($this->monthlyDailyReport as $pointDate => $pointDownloads)
{
	$pointDate      = $this->escape($pointDate);
	$pointDownloads = (int)$pointDownloads;

	$pointsJavascript .= <<< JS
dlPoints.push(['$pointDate', parseInt('$pointDownloads' * 100) * 1 / 100]);

JS;

}

$js = <<< JS

akeeba.jQuery(document).ready(function ($)
{
	$.jqplot.config.enablePlugins = true;
	var dlPoints = [];
	{$pointsJavascript}


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
});

JS;


?>

@section('graphs')
	{{--@css('media://com_ars/css/jquery.jqplot.min.css')--}}
	@js('media://com_ars/js/jquery.jqplot.min.js')
	@js('media://com_ars/js/jqplot.dateAxisRenderer.min.js')
	@js('media://com_ars/js/jqplot.hermite.js')
	@js('media://com_ars/js/jqplot.highlighter.min.js')
	@js('media://com_ars/js/jquery.colorhelpers.min.js')

	<h3>
		@lang('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL')
	</h3>

	<div id="mdrChart"></div>

	<h3>
		@lang('COM_ARS_CPANEL_DLSTATSDETAILS_LABEL')
	</h3>

	<table border="0" width="100%" class="table table-striped">
		<tr>
			<td class="dlstats-label">
				@lang('COM_ARS_CPANEL_DL_EVER_LABEL')
			</td>
			<td>
				<?php echo number_format($this->downloadsEver, 0) ?>
			</td>
		</tr>
		<tr>
			<td class="dlstats-label">
				@lang('COM_ARS_CPANEL_DL_THISMONTH_LABEL')
			</td>
			<td>
				<?php echo number_format($this->downloadsMonth, 0) ?>
			</td>
		</tr>
		<tr>
			<td class="dlstats-label">
				@lang('COM_ARS_CPANEL_DL_THISWEEK_LABEL')
			</td>
			<td>
				<?php echo number_format($this->downloadsWeek, 0) ?>
			</td>
		</tr>
	</table>

	<div style="clear: both;">&nbsp;</div>

	<h3>
		@lang('COM_ARS_CPANEL_POPULAR_WEEK_LABEL')
	</h3>

	<?php if (empty($this->popularInWeek)): ?>
	<p>
		@lang('COM_ARS_COMMON_NOITEMS_LABEL')
	</p>
	<?php else: ?>
	@foreach($this->popularInWeek as $item)
		<div class="dlpopular">
			<div class="dlbasic">
				<a class="dltitle"
				   href="index.php?option=com_ars&view=download&id=<?php echo (int)$item->item_id ?>">
					{{{ $item->title }}}
				</a>
				<span class="dltimes">
					{{ (int) $item->dl }}
				</span>
			</div>
			<div class="dladvanced">
				<span class="dlcategory">
					{{{ $item->category }}}
				</span>
				<span class="dlversion">
					{{{ $item->version }}}
				</span>
			</div>
		</div>
	@endforeach
	<?php endif; ?>

	@inlineJs($js)
@stop