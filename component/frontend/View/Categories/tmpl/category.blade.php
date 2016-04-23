<?php
defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Categories\Html $this */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Admin\Helper\Format;

$category_url = Router::_('index.php?option=com_ars&view=Releases&category_id=' . $item->id . '&Itemid=' . $Itemid);

if (!Filter::filterItem($item, false, $this->getContainer()->platform->getUser()->getAuthorisedViewLevels()) && !empty($item->redirect_unauth))
{
	$category_url = $item->redirect_unauth;
}
?>
<div class="ars-category-{{{ $id }}}">

	<h4 class="{{ $item->type == 'bleedingedge' ? 'warning' : '' }}">
		<a href="{{ htmlentities($category_url) }}">
			{{{ $item->title }}}
		</a>
	</h4>
	<p>
		<button class="btn btn-link" type="button" data-toggle="collapse"
				data-target="#ars-category-{{{ $id }}}-info" aria-expanded="false"
				aria-controls="ars-category-{{{ $id }}}-info">
			<span class="glyphicon glyphicon-info-sign"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>

		<a href="{{ htmlentities($category_url) }}" class="btn btn-link">
			<span class="glyphicon glyphicon-folder-open"></span>
			@lang('COM_ARS_CATEGORIES_AVAILABLEVERSIONS')
		</a>
	</p>

	<div class="collapse" id="ars-category-{{{ $id }}}-info">
		<div class="ars-browse-category well">
			<div class="ars-category-description">
				{{ Format::preProcessMessage($item->description, 'com_ars.category_description') }}
			</div>
		</div>
	</div>
</div>
<div class="clearfix"></div>