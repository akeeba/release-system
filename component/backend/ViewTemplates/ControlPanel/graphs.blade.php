<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

$downloadsReport = array_map(function ($date, $count) {
	return [
			'date'  => $date,
			'count' => $count,
	];
}, array_keys($this->monthlyDailyReport), $this->monthlyDailyReport);

$this->getContainer()->platform->addScriptOptions('akeeba.ReleaseSystem.ControlPanel.downloadsReport', $downloadsReport);

?>

@section('graphs')
	@js('media://com_ars/js/Chart.bundle.min.js', $this->getContainer()->mediaVersion)
	@js('media://com_ars/js/ControlPanel.min.js', $this->getContainer()->mediaVersion)

	<div class="akeeba-panel--info">
		<header class="akeeba-block-header">
			<h3>@lang('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL')</h3>
		</header>

		<canvas id="mdrChart" width="400" height="200"></canvas>
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
@stop
