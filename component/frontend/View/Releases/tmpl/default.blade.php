<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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

	<div class="ars-releases">
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

	<form id="ars-pagination" action="{{ \JURI::getInstance()->toString() }}" method="post">
		<input type="hidden" name="option" value="com_ars" />
		<input type="hidden" name="view" value="Releases" />
		<input type="hidden" name="category_id" value="{{{ $this->category->id }}}" />

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
		@lang('ARS_RELEASES_PER_PAGE')
		{{ $this->pagination->getLimitBox() }}
		@endif
	</form>
</div>