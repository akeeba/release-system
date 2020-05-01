<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Releases\Html */

defined('_JEXEC') or die;

$user = $this->getContainer()->platform->getUser();
$i = 0;

?>
@jhtml('formbehavior.chosen')

@extends('admin:com_ars/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('category_id', \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(), 'COM_ARS_COMMON_CATEGORY_SELECT_LABEL', ['class' => 'advancedSelect'])
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('version', 'version', 'COM_ARS_RELEASES_FIELD_VERSION')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('maturity', \Akeeba\ReleaseSystem\Admin\Helper\Select::maturity(false), 'COM_ARS_RELEASES_MATURITY_SELECT')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('access', \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel(), 'COM_ARS_COMMON_SHOW_ALL_LEVELS', ['class' => 'advancedSelect'])
    </div>
@stop

@section('browse-table-header')
    <tr data-dragable-group="1">
        <th width="8%">
            @sortgrid('ordering', '<i class="icon-menu-2"></i>')
        </th>
        <th width="32">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th>
            @sortgrid('category_id', 'COM_ARS_RELEASES_FIELD_CATEGORY')
        </th>
        <th>
            @sortgrid('version', 'COM_ARS_RELEASES_FIELD_VERSION')
        </th>
        <th>
            @sortgrid('maturity', 'COM_ARS_RELEASES_FIELD_MATURITY')
        </th>
        <th>
            @sortgrid('created', 'COM_ARS_RELEASES_FIELD_RELEASED')
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
    @foreach($this->items as $row)
		<?php
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $row */
		?>
        <tr>
            <td>
                @jhtml('FEFHelper.browse.order', 'ordering', $row->ordering)
            </td>
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                {{{ \Akeeba\ReleaseSystem\Admin\Model\Categories::forceEagerLoad($row->category_id, 'title') }}}
            </td>
            <td>
                @if ($user->authorise('core.admin') || $user->authorise('core.edit', 'com_ars.category.' . $row->category_id))
                    <a href="index.php?option=com_ars&view=Release&id={{{ $row->getId() }}}">
                        {{{ $row->version }}}
                    </a>
                @else
                    {{{ $row->version }}}
                @endif
            </td>
            <td>
                @if (in_array($row->maturity, ['alpha', 'beta', 'rc', 'stable']))
                    @lang('COM_ARS_RELEASES_MATURITY_'. $row->maturity)
                @else
                    @lang('COM_ARS_RELEASES_MATURITY_ALPHA')
                @endif
            </td>
            <td>
                {{ \Akeeba\ReleaseSystem\Admin\Helper\Format::formatDate($row->created, true) }}

            </td>
            <td>
                {{ \Akeeba\ReleaseSystem\Admin\Helper\Html::accessLevel($row->access) }}
            </td>
            <td>
                @jhtml('jgrid.published', $row->published, $i, '', $user->authorise('core.edit.state', 'com_ars'), 'cb')
            </td>
            <td>
                {{ \Akeeba\ReleaseSystem\Admin\Helper\Html::language($row->language) }}
            </td>
        </tr>
    @endforeach
@stop