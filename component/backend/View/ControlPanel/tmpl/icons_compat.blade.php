<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 *
 * This file renders the control panel icons when Akeeba Strapper is not available or not loaded
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html  $this */

$lang = JFactory::getLanguage();
$icons_root = JURI::base() . 'components/com_ars/assets/images/';

$groups = array('basic', 'tools', 'update');

?>
@section('icons')
	@foreach(['basic', 'tools', 'update'] as $group)
		@if (array_key_exists($group, $this->iconDefinitions))
			@if (count($this->iconDefinitions[$group]))
				<h3>
					@lang('LBL_ARS_CPANEL_' . $group)
				</h3>

				@foreach ($this->iconDefinitions[$group] as $icon)
                <a href="index.php?option=com_ars{{{ is_null($icon['view']) ? '' : '&view=' . $icon['view']  }}}{{{ is_null($icon['task']) ? '' : '&task=' . $icon['task']  }}}"
                        class="btn" style="width: 9em; height: 6em; margin: 0.5em">
						<div class="ak-icon ak-icon-{{{ $icon['icon'] }}}">&nbsp;</div>
						<span>{{{ $icon['label'] }}}</span>
				</a>
				@endforeach

				<div class="ak_clr_left"></div>
			@endif
		@endif
	@endforeach


@stop