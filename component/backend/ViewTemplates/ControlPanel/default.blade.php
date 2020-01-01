<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

defined('_JEXEC') or die;
?>

@include('admin:com_ars/ControlPanel/footer')
@include('admin:com_ars/ControlPanel/graphs')
@include('admin:com_ars/ControlPanel/icons_compat')
@include('admin:com_ars/ControlPanel/phpversion')

@yield('phpVersionWarning', '')

@if($this->needsMenuItem)
<div class="akeeba-block--info">
	<h4>
		@lang('COM_ARS_MISSING_CATEGORIES_MENU_HEAD')
	</h4>
	@lang('COM_ARS_MISSING_CATEGORIES_MENU')
</div>
@endif

{{-- This DIV is required to render the update notification, if there is an update available --}}
<div id="updateNotice"></div>

<div class="akeeba-container--50-50">
	<div>
		@yield('graphs')
	</div>
	<div>
		@yield('icons')
	</div>
</div>

<div>
	@yield('footer')
</div>
