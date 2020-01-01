<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Latest\Html $this */
?>
<div class="ars-categories-{{ $section }}">
	@unless(empty($title))
		<div class="page-header">
			<h2>@lang($title)</h2>
		</div>
	@endunless

	@if(empty($this->categories))
		<p class="muted ars-no-items">
			@lang('ARS_NO_CATEGORIES')
		</p>
	@else
		@foreach($this->categories->filter(function ($item)
            {
                return \Akeeba\ReleaseSystem\Site\Helper\Filter::filterItem($item, true);
            }) as $id => $item)
			@if(($item->type == $section) || ($section == 'all'))
				@include('site:com_ars/Latest/category', ['id' => $id, 'item' => $item, 'Itemid' => $this->Itemid])
			@endif
		@endforeach
	@endif
</div>
