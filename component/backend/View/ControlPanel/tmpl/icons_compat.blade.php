<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html  $this */

$lang = JFactory::getLanguage();
$icons_root = JURI::base() . 'components/com_ars/assets/images/';

$groups = array('basic', 'tools', 'update');

?>
@section('icons')
	<div class="akeeba-panel--primary">
		<header class="akeeba-block-header">
			<h3>@lang('LBL_ARS_CPANEL_BASIC')</h3>
		</header>

		<div class="akeeba-grid--small">
			<a href="index.php?option=com_ars&view=VisualGroups" class="akeeba-action--teal">
				<span class="akion-folder"></span>
				@lang('COM_ARS_TITLE_VISUALGROUPS')
			</a>

			<a href="index.php?option=com_ars&view=Categories" class="akeeba-action--teal">
				<span class="akion-folder"></span>
				@lang('COM_ARS_TITLE_CATEGORIES')
			</a>

			<a href="index.php?option=com_ars&view=Releases" class="akeeba-action--teal">
				<span class="akion-folder"></span>
				@lang('COM_ARS_TITLE_RELEASES')
			</a>

			<a href="index.php?option=com_ars&view=Items" class="akeeba-action--grey">
				<span class="akion-ios-list"></span>
				@lang('COM_ARS_TITLE_ITEMS')
			</a>

			<a href="index.php?option=com_ars&view=Environments" class="akeeba-action--orange">
				<span class="akion-grid"></span>
				@lang('COM_ARS_TITLE_ENVIRONMENTS')
			</a>

			<a href="index.php?option=com_ars&view=DownloadIDLabels" class="akeeba-action--red">
				<span class="akion-lock-combination"></span>
				@lang('COM_ARS_TITLE_DOWNLOADIDLABELS')
			</a>
		</div>
	</div>

	<div class="akeeba-panel--primary">
		<header class="akeeba-block-header">
			<h3>@lang('LBL_ARS_CPANEL_TOOLS')</h3>
		</header>

		<div class="akeeba-grid--small">
			<a href="index.php?option=com_ars&view=AutoDescriptions" class="akeeba-action--grey">
				<span class="akion-wand"></span>
				@lang('COM_ARS_TITLE_AUTODESCRIPTIONS')
			</a>

			<a href="index.php?option=com_ars&view=UpdateStreams" class="akeeba-action">
				<span class="akion-information-circled"></span>
				@lang('COM_ARS_TITLE_UPDATESTREAMS')
			</a>

			<a href="index.php?option=com_ars&view=Logs" class="akeeba-action--teal">
				<span class="akion-stats-bars"></span>
				@lang('COM_ARS_TITLE_LOGS')
			</a>
		</div>
	</div>
@stop
