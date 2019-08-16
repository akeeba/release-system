<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

$pointsJavascript = '';
$min = null;
$max = null;

foreach ($this->monthlyDailyReport as $pointDate => $pointDownloads)
{
	$min            = is_null($min) ? $pointDate : $min;
	$max            = $pointDate;
	$pointDate      = $this->escape($pointDate);
	$pointDownloads = (int) $pointDownloads;

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
			    min:          '{$min}',
			    max:          '{$max}',
				renderer:     $.jqplot.DateAxisRenderer
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

	<div class="akeeba-panel--info">
		<header class="akeeba-block-header">
			<h3>@lang('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL')</h3>
		</header>

		<div id="mdrChart"></div>
	</div>

	<div class="akeeba-panel--info">
		<header class="akeeba-block-header">
			<h3>@lang('COM_ARS_CPANEL_DLSTATSDETAILS_LABEL')</h3>
		</header>

		<table class="akeeba-table--striped">
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
	</div>

	@inlineJs($js)
@stop
