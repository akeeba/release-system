<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Model\Releases;

/** @var \Akeeba\ReleaseSystem\Admin\View\Items\Html $this */
/** @var bool $modal */

defined('_JEXEC') or die;

$i = 0;
$user = $this->getContainer()->platform->getUser();

$modal = isset($modal) ? boolval($modal) : false;
$filterCat = (int) $this->getModel()->getState('category', 0);
?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/browse')

@section('browse-filters')
	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('category', \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(false, false), 'COM_ARS_COMMON_CATEGORY_SELECT_LABEL', ['class' => 'advancedSelect'])
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('release', \Akeeba\ReleaseSystem\Admin\Helper\Select::releases(false, $filterCat), 'COM_ARS_COMMON_SELECT_RELEASE_LABEL', ['class' => 'advancedSelect'])
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@searchfilter('title', 'title', 'LBL_ITEMS_TITLE')
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('type', \Akeeba\ReleaseSystem\Admin\Helper\Select::itemType(false), 'LBL_ITEMS_TYPE_SELECT')
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('access', \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel(), 'COM_ARS_COMMON_SHOW_ALL_LEVELS', ['class' => 'advancedSelect'])
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \FOF40\Html\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('language', \Akeeba\ReleaseSystem\Admin\Helper\Select::languages(), 'JFIELD_LANGUAGE_LABEL')
	</div>
@stop

@section('browse-table-header')
	<tr>
		<th width="8%">
			@sortgrid('ordering', '<i class="icon-menu-2"></i>')
		</th>
		<th width="32">
			@jhtml('FEFHelp.browse.checkall')
		</th>
		<th>
			@lang('LBL_ITEMS_CATEGORY')
		</th>
		<th>
			@sortgrid('release', 'LBL_ITEMS_RELEASE')
		</th>
		<th>
			@sortgrid('title', 'LBL_ITEMS_TITLE')
		</th>
		<th>
			@sortgrid('type', 'LBL_ITEMS_TYPE')
		</th>
		<th>
			@lang('LBL_ITEMS_ENVIRONMENTS')
		</th>
		<th>
			@sortgrid('access', 'JFIELD_ACCESS_LABEL')
		</th>
		<th width="8%">
			@sortgrid('published', 'JPUBLISHED')
		</th>
		<th>
			@sortgrid('hits', 'JGLOBAL_HITS')
		</th>
		<th>
			@sortgrid('language', 'JFIELD_LANGUAGE_LABEL')
		</th>
	</tr>
@stop

@section('browse-table-body-withrecords')
	@foreach($this->items as $row)
		<?php
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $row */

		$category_id = Releases::forceEagerLoad($row->release_id, 'category_id');
		$canEdit = $user->authorise('core.admin') || $user->authorise('core.edit', 'com_ars.category.' . $category_id);
		$enabled = $user->authorise('core.edit.state', 'com_ars')
		?>
		<tr data-dragable-group="1">
			<td>
				@jhtml('FEFHelp.browse.order', 'ordering', $row->ordering)
			</td>
			<td>
				@jhtml('FEFHelp.browse.id', ++$i, $row->getId())
			</td>
			<td>
				{{{ \Akeeba\ReleaseSystem\Site\Model\Categories::forceEagerLoad($category_id, 'title') }}}
			</td>
			<td>
				{{{ \Akeeba\ReleaseSystem\Site\Model\Releases::forceEagerLoad($row->release_id, 'version') }}}
			</td>
			<td>
				@if ($modal)
					<a href="javascript:arsItemsProxy('{{{ $row->id }}}', '{{{ $row->title }}}')">
						{{{ $row->title }}}
					</a>

				@elseif ($canEdit)
					<a href="index.php?option=com_ars&view=Item&id={{ $row->id }}">
						{{{ $row->title }}}
					</a>
				@else
					{{{ $row->title }}}
				@endif
			</td>
			<td>
				@if ($row->type == 'link')
					@lang('LBL_ITEMS_TYPE_LINK')
				@else
					@lang('LBL_ITEMS_TYPE_FILE')
				@endif
			</td>
			<td>
				@foreach ($row->environments as $environment)
					<span class="akeeba-label--teal ars-environment-icon">{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::environmentTitle((int)$environment) }}</span>
				@endforeach
			</td>
			<td>
				{{ \Akeeba\ReleaseSystem\Admin\Helper\Html::accessLevel($row->access) }}
			</td>
			<td>
				@jhtml('FEFHelp.browse.published', $row->published, $i, '', $enabled)
			</td>
			<td>
				{{{ $row->hits }}}
			</td>
			<td>
				{{ \Akeeba\ReleaseSystem\Admin\Helper\Html::language($row->language) }}
			</td>
		</tr>
	@endforeach
@stop
