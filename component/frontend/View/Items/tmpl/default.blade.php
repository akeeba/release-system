<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Items\Html  $this */

$released   = \JFactory::getDate($this->release->created);
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
	<div class="page-header">
		<h1>
			{{{ $this->params->get('page_heading', $this->menu->title) . ' ' . $this->release->version }}}
		</h1>
	</div>
	@endif

	@include('site:com_ars/Items/release', ['id' => $this->release->id, 'item' => $this->release, 'Itemid' => $this->Itemid, 'no_link' => true])

	<div class="ars-items">
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

	<form id="ars-pagination" action="{{ \JURI::getInstance()->toString() }}" method="post">
		<input type="hidden" name="option" value="com_ars" />
		<input type="hidden" name="view" value="Items" />
		<input type="hidden" name="release_id" value="{{{ $this->release->id }}}" />

		@if ($this->params->get('show_pagination', 1))
			@if($this->pagination->pagesTotal > 1)
			<div class="pagination">

				@if($this->params->get('show_pagination_results', 1))
				<p class="counter">
					{{ $this->pagination->getPagesCounter() }}
				</p>
				@endif

				{{ $this->pagination->getPagesLinks() }}
			</div>

			@endif
		@lang('ARS_ITEMS_PER_PAGE')
		{{ $this->pagination->getLimitBox() }}
		@endif
	</form>
</div>