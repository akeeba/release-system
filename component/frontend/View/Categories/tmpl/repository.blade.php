<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Categories\Html  $this */
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
	<div class="page-header">
		<h1>{{{ $this->params->get('page_heading', $this->menu->title) }}}</h1>
	</div>
	@endif

	@if($this->params->get('grouping', 'normal') == 'none')
		@include('site:com_ars/Categories/generic', ['section' => 'all', 'title' => ''])
	@else
		@include('site:com_ars/Categories/generic', ['section' => 'normal', 'title' => 'ARS_CATEGORY_NORMAL'])
		@include('site:com_ars/Categories/generic', ['section' => 'bleedingedge', 'title' => 'ARS_CATEGORY_BLEEDINGEDGE'])
	@endif
</div>
