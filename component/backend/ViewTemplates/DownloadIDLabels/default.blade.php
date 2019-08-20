<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\ReleaseSystem\Admin\View\DownloadIDLabels\Html */

defined('_JEXEC') or die;

?>
@extends('admin:com_ars/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('label', 'label', 'COM_ARS_DLIDLABELS_FIELD_LABEL')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('username', 'username', 'JGLOBAL_USERNAME')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('dlid', 'dlid', 'COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID')
    </div>
@stop

@section('browse-table-header')
    <tr>
        <th width="32">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th>
            @sortgrid('label', 'COM_ARS_DLIDLABELS_FIELD_LABEL')
        </th>
        <th>
            @sortgrid('user_id', 'JGLOBAL_USERNAME')
        </th>
        <th>
            @sortgrid('dlid', 'COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID')
        </th>
        <th width="8%">
            @sortgrid('enabled', 'JPUBLISHED')
        </th>
        <th>
            @lang('COM_ARS_DLIDLABELS_FIELD_RESET')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
	<?php $i = 0; ?>
    @foreach($this->items as $row)
		<?php
		/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $row */
		$enabled = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars')
		?>
        <tr>
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                @if (($row->label == '_MAIN_') || $row->primary)
                    <span class="akeeba-label--green">@lang('COM_ARS_DLIDLABELS_LBL_DEFAULT')</span>
                @else
                    <a href="index.php?option=com_ars&view=DownloadIDLabel&task=edit&id={{ $row->ars_dlidlabel_id }}">
                        {{{ $row->label }}}
                    </a>
                @endif
            </td>
            <td>
                @include('admin:com_ars/Common/ShowUser', ['item' => $row, 'field' => 'user_id'])
            </td>
            <td>
				<span class="{{ $row->primary ? 'akeeba-label--dark' : '' }}">
					{{ $row->primary ? '' : $row->user_id . ':' }}{{ $row->dlid }}
				</span>
            </td>
            <td>
                @jhtml('FEFHelper.browse.published', $row->enabled, $i, '', $enabled)
            </td>
            <td>
                <a href="index.php?option=com_ars&view=DownloadIDLabels&task=reset&id={{{ $row->ars_dlidlabel_id }}}&{{{ $this->getContainer()->platform->getToken(true) }}}=1"
                   class="akeeba-btn--orange--small">
                    <span class="akion-refresh"></span>
                    @lang('COM_ARS_DLIDLABELS_FIELD_RESET')
                </a>
            </td>
        </tr>
    @endforeach
@stop
