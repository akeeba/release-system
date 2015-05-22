<?php
defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Browse\Html  $this */
?>
<div class="ars-categories-{{ $section }}">
	@unless(empty($title))
		<div class="page-header">
			<h2>@lang($title)</h2>
		</div>
	@endunless

	@if(empty($this->items))
		<p class="muted ars-no-items">
			@lang('ARS_NO_CATEGORIES')
		</p>
	@else
		@foreach($this->vgroups as $vgroup)
			@unless($vgroup->numitems[$section] == 0)
				<div class="ars-vgroup-{{{ $vgroup->id }}}">
					@unless(empty($vgroup->title))
						<h3 class="ars-vgroup-{{{ $vgroup->id }}}-title">
							{{{ $vgroup->title }}}
						</h3>

						@unless(empty($vgroup->description))
							<div class="ars-vgroup-{{{ $vgroup->id }}}-description">
								{{ $vgroup->description }}
							</div>
						@endunless
					@endunless

					@foreach($this->items[$section] as $id => $item)
						@unless($item->vgroup_id != $vgroup->id)
							@include('site:com_ars/Browse/category', ['id' => $id, 'item' => $item, 'Itemid' => $this->Itemid])
						@endunless
					@endforeach
				</div>
			@endunless
		@endforeach
	@endif
</div>