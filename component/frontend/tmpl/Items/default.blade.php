<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Items\Html $this */

?>
@js('media://com_ars/js/InfoToggler.js', $this->getContainer()->mediaVersion)

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
		<div class="page-header">
			<h2>
				{{{ $this->params->get('page_heading', $this->menu->title) . ' ' . $this->release->version }}}
			</h2>
		</div>
	@endif

	@include('site:com_ars/Items/release', ['id' => $this->release->id, 'item' => $this->release, 'Itemid' => $this->Itemid, 'no_link' => true])

	<div class="ars-items ars-items-{{ $this->release->category->is_supported ? 'supported' : 'unsupported' }}">
		@if(count($this->items))
			@foreach($this->items as $item)
				@include('site:com_ars/Items/item', ['item' => $item, 'Itemid' => $this->Itemid])
			@endforeach
	@else
		<div class="ars-noitems">
			@lang('ARS_NO_ITEMS')
		</div>
	@endif
	</div>
</div>
