<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Model\Releases;

/** @var \Akeeba\ReleaseSystem\Admin\View\Items\Html $this */
/** @var bool $modal */

defined('_JEXEC') or die;

$i = 0;
$user = $this->getContainer()->platform->getUser();

$modal = isset($modal) ? boolval($modal) : false;

?>
@jhtml('formbehavior.chosen')

@extends('admin:com_ars/Common/browse')

@section('browse-filters')
	<div class="akeeba-filter-element akeeba-form-group">
		{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::categories($this->filters['category'], 'category', ['onchange' => 'document.adminForm.submit()', 'class' => 'advancedSelect'], false) }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::releases($this->filters['release'], 'release', ['onchange' => 'document.adminForm.submit()', 'class' => 'advancedSelect']) }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		@searchfilter('title', 'title', 'LBL_ITEMS_TITLE')
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::itemType('type', $this->filters['type'], ['onchange' => 'document.adminForm.submit()']) }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel('access', $this->filters['access'], ['onchange' => 'document.adminForm.submit()', 'class' => 'advancedSelect']) }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
        {{ \FOF30\Utils\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::languages('language', $this->filters['language'], ['onchange' => 'document.adminForm.submit()']) }}
	</div>
@stop

@section('browse-table-header')
	<tr>
		<th width="8%">
			@sortgrid('ordering', '<i class="icon-menu-2"></i>')
		</th>
		<th width="32">
			@jhtml('FEFHelper.browse.checkall')
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
		<tr>
			<td>
				@jhtml('FEFHelper.browse.order', 'ordering', $row->ordering)
			</td>
			<td>
				@jhtml('FEFHelper.browse.id', ++$i, $row->getId())
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
				@jhtml('FEFHelper.browse.published', $row->published, $i, '', $enabled)
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
