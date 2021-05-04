<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Component\ARS\Administrator\Helper\Html;use Akeeba\Component\ARS\Administrator\Helper\Select;use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

$i = 0;

$siteRoot = substr(rtrim(\Joomla\CMS\Uri\Uri::base(), '/'), 0, -13);

?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/browse')

@section('browse-page-top')
    <div class="akeeba-block--info">
        @lang('COM_ARS_STREAM_LBL_ALLLINKS_INTRO')
        <a href="<?php echo Uri::root() ?>index.php?option=com_ars&view=update&task=all&format=xml" target="_blank">
            @lang('COM_ARS_STREAM_LBL_ALLLINKS')
        </a>
    </div>
@stop

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('name', 'name', 'COM_ARS_STREAM_FIELD_NAME')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('element', 'element', 'COM_ARS_STREAM_FIELD_ELEMENT')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('type', \Akeeba\Component\ARS\Administrator\Helper\Select::updateTypes(), 'COM_ARS_STREAM_FIELD_TYPE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('category', \Akeeba\Component\ARS\Administrator\Helper\Select::categories(), 'COM_ARS_COMMON_CATEGORY_SELECT_LABEL', ['class' => 'advancedSelect'])
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
            @sortgrid('name', 'COM_ARS_STREAM_FIELD_NAME')
        </th>
        <th>
            @sortgrid('type', 'COM_ARS_STREAM_FIELD_TYPE')
        </th>
        <th>
            @sortgrid('category', 'COM_ARS_RELEASES_FIELD_CATEGORY')
        </th>
        <th>
            @sortgrid('element', 'COM_ARS_STREAM_FIELD_ELEMENT')
        </th>
        <th>
            @lang('COM_ARS_STREAM_LBL_LINKS')
        </th>
        <th>
            @sortgrid('published', 'JPUBLISHED')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
    @foreach($this->items as $row)
		<?php
		/** @var \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams $row */
		$link = 'index.php?option=com_ars&view=UpdateStream&id=' . $row->id;
		?>
        <tr>
            <td>
                @jhtml('FEFHelp.browse.id', ++$i, $row->getId())
            </td>
            <td>
                <a href="index.php?option=com_ars&view=UpdateStream&id={{{ $row->id }}}">
                    {{{ $row->name }}}<br />
                    <span class="small">({{{ $row->alias }}})</span>
                </a>
            </td>
            <td>
                {{ \Akeeba\Component\ARS\Administrator\Helper\Html::decodeUpdateType($row->type) }}
            </td>
            <td>
                {{{ $row->categoryObject->title }}}
            </td>
            <td>
                {{{ $row->element }}}
            </td>
            <td>
                <a href="{{ $siteRoot }}index.php?option=com_ars&view=update&format=ini&id={{{ $row->id }}}"
                   target="_blank">INI</a>
                &bull;
                <a href="{{ $siteRoot }}index.php?option=com_ars&view=update&task=stream&format=xml&id={{{ $row->id }}}"
                   target="_blank">XML</a>
                &bull;
                <a href="{{ $siteRoot }}index.php?option=com_ars&view=update&task=download&format=raw&id={{{ $row->id }}}"
                   download
                   target="_blank">D/L</a>

            </td>
            <td>
                @jhtml('FEFHelp.browse.published', $row->published, $i, '', $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars'))
            </td>
        </tr>
    @endforeach
@stop

