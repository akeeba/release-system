<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Releases\Html  $this */
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
	<div class="page-header">
		<h1>
			{{{ $this->params->get('page_heading', $this->menu->title) }}}
		</h1>
	</div>
	@endif

	@include('site:com_ars/Releases/category', ['id' => $this->category->id, 'item' => $this->category, 'Itemid' => $this->Itemid, 'no_link' => true])

	<div class="ars-releases ars-releases-{{ $this->category->is_supported ? 'supported' : 'unsupported' }}">
	@if(count($this->items))
		@foreach($this->items as $item)
				@include('site:com_ars/Releases/release', ['item' => $item, 'Itemid' => $this->Itemid])
		@endforeach
	@else
		<div class="ars-noitems">
			@lang('ARS_NO_RELEASES')
		</div>
	@endif
	</div>
</div>
