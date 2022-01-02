<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Component\ARS\Administrator\View\Controlpanel\HtmlView $this */

use Joomla\CMS\Language\Text;

?>

<div class="card">
	<h3 class="card-header">
		<?= Text::_('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL') ?>
	</h3>
	<div class="card-body">
		<canvas id="mdrChart" width="400" height="200"></canvas>
	</div>
</div>

<div class="card">
	<h3 class="card-header">
		<?= Text::_('COM_ARS_CPANEL_DLSTATSDETAILS_LABEL') ?>
	</h3>
	<div class="card-body">
		<table class="table table-striped">
			<tr>
				<th class="dlstats-label" scope="row">
					<?= Text::_('COM_ARS_CPANEL_DL_THISMONTH_LABEL') ?>
				</th>
				<td>
					<?= number_format($this->downloadsMonth, 0) ?>
				</td>
			</tr>
			<tr>
				<th class="dlstats-label" scope="row">
					<?= Text::_('COM_ARS_CPANEL_DL_THISWEEK_LABEL') ?>
				</th>
				<td>
					<?= number_format($this->downloadsWeek, 0) ?>
				</td>
			</tr>
		</table>
	</div>
</div>