<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;use Akeeba\ReleaseSystem\Admin\Helper\Select;use Akeeba\ReleaseSystem\Admin\Model\Categories;use FOF30\Utils\FEFHelper\Html as FEFHtml;use Joomla\CMS\HTML\HTMLHelper;use Joomla\CMS\Language\Text;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Categories\Html */

defined('_JEXEC') or die;

?>
@jhtml('formbehavior.chosen')

@extends('admin:com_ars/Common/browse')

@section('browse-filters')
	<div class="akeeba-filter-element akeeba-form-group">
		@selectfilter('type', \Akeeba\ReleaseSystem\Admin\Helper\Select::categoryType(), 'COM_ARS_LBL_COMMON_SELECTCATTYPE')
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		<?php echo Select::accessLevel('access', $this->filters['access'], [
				'onchange' => 'document.adminForm.submit()', 'class' => 'advancedSelect'
		]);?>
	</div>

	<div class="akeeba-filter-element akeeba-form-group">
		{{ \FOF30\Utils\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
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
			@jhtml('FEFHelper.browse.checkall')
		</th>
		<th>
			@sortgrid('title', 'COM_ARS_CATEGORIES_FIELD_TITLE')
		</th>
		<th>
			@sortgrid('type', 'COM_ARS_CATEGORIES_FIELD_TYPE')
		</th>
		<th>
			@sortgrid('access', 'JFIELD_ACCESS_LABEL')
		</th>
		<th width="8%">
			@sortgrid('published', 'JPUBLISHED')
		</th>
		<th>
			@sortgrid('language', 'JFIELD_LANGUAGE_LABEL')
		</th>
	</tr>
@stop

@section('browse-table-body-withrecords')
	<?php
	$i = 0;
	/** @var Categories $row */
	?>
	@foreach($this->items as $row)
		<?php
		$enabled = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars')
		?>
		<tr>
			<td>
				@jhtml('FEFHelper.browse.order', 'ordering', $row->ordering)
			</td>
			<td>
				@jhtml('FEFHelper.browse.id', ++$i, $row->getId())
			</td>
			<td>
				<a href="index.php?option=com_ars&view=Category&id={{ $row->getId() }}">
					{{{ $row->title }}}
				</a>
			</td>
			<td>
				@if ($row->type == 'normal')
					@lang('COM_ARS_CATEGORIES_TYPE_NORMAL')
				@else
					@lang('COM_ARS_CATEGORIES_TYPE_BLEEDINGEDGE')
				@endif
			</td>
			<td>
				{{ Html::accessLevel($row->access) }}
			</td>
			<td>
				@jhtml('FEFHelper.browse.published', $row->published, $i, '', $enabled)
			</td>
			<td>
				{{ Html::language($row->language) }}
			</td>
		</tr>
	@endforeach
@stop