<?php
/**
 * package   AkeebaReleaseSystem
 * copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html  $this */

defined('_JEXEC') or die;
?>
@css('media://com_ars/css/backend.css')
@js('media://com_ars/js/gui-helpers.js')

@jhtml('behavior.core')
@jhtml('formbehavior.chosen', 'select')

{{-- Include external sections. Do note how you can include sub-templates in one order and compile them in a completely
different order using @yield later on! --}}
@include('admin:com_ars/ControlPanel/footer')
@include('admin:com_ars/ControlPanel/graphs')
@if(class_exists('\\AkeebaStrapper30'))
    @include('admin:com_ars/ControlPanel/icons')
@else
    @include('admin:com_ars/ControlPanel/icons_compat')
@endif
@include('admin:com_ars/ControlPanel/phpversion')

{{-- Note: I don't pass $this->hasGeoIPPlugin and $this->geoIPPluginNeedsUpdate. This demonstrates how Blade
subtemplates can view their parent's variables automatically. --}}
@include('admin:com_ars/ControlPanel/geoip')

{{-- Compile the output. Do note that I don't need to wrap it in a section. Content outside a section is yielded
immediately. Alternatively I could wrap this in a @section/@show block or even @section/@stop and use @yield to
render it. --}}
@yield('phpVersionWarning', '')

@if($this->needsMenuItem)
<div class="alert alert-info">
	<h4>
		@lang('COM_ARS_MISSING_CATEGORIES_MENU_HEAD')
	</h4>
	@lang('COM_ARS_MISSING_CATEGORIES_MENU')
</div>
@endif

{{-- This DIV is required to render the update notification, if there is an update available --}}
<div id="updateNotice"></div>

@yield('geoip', '')

<div class="row-fluid">
	<div id="cpanel" class="span<?php echo $this->graphsWidth ?>">
		@yield('graphs')
	</div>
	<div id="cpanel" class="span<?php echo 12 - $this->graphsWidth ?>">
		@yield('icons')
	</div>
</div>

<div class="ak_clr"></div>

<div class="row-fluid footer">
	<div class="span12">
		@yield('footer')
	</div>
</div>

{{ $this->statsIFrame }}

{{-- This Javascript is required to render the update notification, if there is an update available --}}
<script type="text/javascript">
	(function($) {
		$(document).ready(function(){
			$.ajax('index.php?option=com_ars&view=ControlPanel&task=updateinfo&tmpl=component', {
				success: function(msg, textStatus, jqXHR)
				{
					// Get rid of junk before and after data
					var match = msg.match(/###([\s\S]*?)###/);
					data = match[1];

					if (data.length)
					{
						$('#updateNotice').html(data);
					}
				}
			})
		});
	})(akeeba.jQuery);
</script>