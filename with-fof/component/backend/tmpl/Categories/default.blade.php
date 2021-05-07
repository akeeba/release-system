<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Component\ARS\Administrator\Helper\Html;use Akeeba\Component\ARS\Administrator\Helper\Select;use Akeeba\ReleaseSystem\Admin\Model\Categories;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Categories\Html */

defined('_JEXEC') or die;

?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('search', 'search', 'COM_ARS_CATEGORIES_FIELD_TITLE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('type', \Akeeba\Component\ARS\Administrator\Helper\Select::categoryType(), 'COM_ARS_COMMON_LBL_SELECTCATTYPE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('access', \Akeeba\Component\ARS\Administrator\Helper\Select::accessLevel(), 'COM_ARS_COMMON_SHOW_ALL_LEVELS', ['class' => 'advancedSelect'])
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        {{ \FOF40\Html\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('language', \Akeeba\Component\ARS\Administrator\Helper\Select::languages(), 'JFIELD_LANGUAGE_LABEL')
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
		<tr data-dragable-group="1">
			<td>
				@jhtml('FEFHelp.browse.order', 'ordering', $row->ordering)
			</td>
			<td>
				@jhtml('FEFHelp.browse.id', ++$i, $row->getId())
			</td>
			<td>
				<a href="index.php?option=com_ars&view=Category&id={{ $row->getId() }}">
					{{{ $row->title }}}
                </a> <br /> <code>{{ $row->directory }}</code>
            </td>
            <td>
                @if ($row->type == 'normal')
                    @lang('COM_ARS_CATEGORIES_TYPE_NORMAL')
                @else
                    @lang('COM_ARS_CATEGORIES_TYPE_BLEEDINGEDGE')
                @endif
            </td>
            <td>
                {{ \Akeeba\Component\ARS\Administrator\Helper\Html::accessLevel($row->access) }}
            </td>
            <td>
                @jhtml('FEFHelp.browse.published', $row->published, $i, '', $enabled)
            </td>
            <td>
                {{ \Akeeba\Component\ARS\Administrator\Helper\Html::language($row->language) }}
            </td>
        </tr>
    @endforeach
@stop