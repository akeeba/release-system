<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Component\ARS\Administrator\Helper\Select;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\AutoDescriptions\Html */

defined('_JEXEC') or die;

?>
@extends('any:lib_fof40/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('title', 'title', 'LBL_AUTODESC_TITLE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('packname', 'packname', 'LBL_AUTODESC_PACKNAME')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('category', \Akeeba\Component\ARS\Administrator\Helper\Select::categories(), 'COM_ARS_COMMON_CATEGORY_SELECT_LABEL')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        {{ \FOF40\Html\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
    </div>
@stop

@section('browse-table-header')
    <tr>
        <th width="32">
            @jhtml('FEFHelp.browse.checkall')
        </th>
        <th>
            @sortgrid('category', 'LBL_AUTODESC_CATEGORY')
        </th>
        <th>
            @sortgrid('packname', 'LBL_AUTODESC_PACKNAME')
        </th>
        <th>
            @sortgrid('title', 'LBL_AUTODESC_TITLE')
        </th>
        <th width="8%">
            @sortgrid('published', 'JPUBLISHED')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
	<?php $i = 0; ?>

    @foreach($this->items as $row)
		<?php
		/** @var \Akeeba\ReleaseSystem\Admin\Model\AutoDescriptions $row */
		$enabled = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars')
		?>
        <tr>
            <td>
                @jhtml('FEFHelp.browse.id', ++$i, $row->getId())
            </td>
            <td>
                {{{ $row->categoryObject->title }}}
            </td>
            <td>
                {{{ $row->packname }}}
            </td>
            <td>
                <a href="index.php?option=com_ars&view=AutoDescription&id={{ $row->getId() }}">
                    {{{ $row->title }}}
                </a>
            </td>
            <td>
                @jhtml('FEFHelp.browse.published', $row->published, $i, '', $enabled)
            </td>
        </tr>
    @endforeach
@stop
