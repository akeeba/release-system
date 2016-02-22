<?php
/**
 * package   AkeebaReleaseSystem
 * copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Site\Helper\Filter;

/** @var \Akeeba\ReleaseSystem\Site\View\DownloadIDLabels\Html $this */

$itemId = $this->input->getInt('Itemid') ? '&Itemid=' . $this->input->getInt('Itemid') : '';
$formURI = JRoute::_('index.php?option=com_ars&view=DownloadIDLabels' . $itemId);
$returnUrl = base64_encode(JUri::getInstance()->toString());

$options = [];
$options[] = JHtml::_('select.option', '1', 'JPUBLISHED');
$options[] = JHtml::_('select.option', '0', 'JUNPUBLISHED');
$options[] = JHtml::_('select.option', '', 'JALL');

?>

<div class="alert alert-info">
    @sprintf('COM_ARS_DLIDLABELS_MASTERDLID', Filter::myDownloadID())
</div>

<form name="arsDownloadID" action="{{ htmlentities($formURI) }}" method="post">
    <input type="hidden" name="task" value="browse" />

    <div class="form form-inline form-search well well-small well-sm">
        <span class="form-group">
            <label for="enabled">
                @lang('JSEARCH_FILTER_LABEL')
            </label>
            @jhtml('select.genericlist', $options, 'enabled', ['onchange' => 'this.form.submit()', 'class' => 'form-control'], 'value', 'text', $this->getModel()->getState('enabled'), false, true)
        </span>

        <span class="form-group">
            <span class="input-group">
                <input type="text" name="label" value="{{ $this->getModel()->getState('label') }}"
                       placeholder="@lang('COM_ARS_DLIDLABELS_FIELD_LABEL')"
                       class="span4 search-query form-control"/>

                <span class="input-group-btn">
                    <button type="submit" class="btn btn-default">
                        <span class="icon icon-search"></span>
                    </button>
                </span>
            </span>
        </span>
    </div>

    <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabel&task=add&' . JFactory::getSession()->getToken() . '=1&returnurl=' . $returnUrl) }}"
       class="btn btn-success">
        <span class="icon icon-white icon-plus"></span>
        @lang('JNEW')
    </a>

    <div class="btn-group pull-right">
        {{ $this->pagination->getLimitBox() }}
    </div>


    <table class="table table-striped" width="100%">
        <thead>
            <tr>
                <th>
                    @lang('COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID')
                </th>
                <th>
                    @lang('COM_ARS_DLIDLABELS_FIELD_LABEL')
                </th>
                <th>
                    @lang('JPUBLISHED')
                </th>
                <th>
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="10" style="text-align: center">
                    {{ $this->pagination->getListFooter() }}
               </td>
            </tr>
        </tfoot>
        <tbody>
        @if($this->items->count())
            @foreach($this->items as $item)
            <tr>
                <td>
                    @include('site:com_ars/DownloadIDLabels/default_dlid', ['item' => $item])
                </td>
                <td>
                    @if($item->primary)
                        @lang('COM_ARS_DLIDLABELS_LBL_DEFAULT')
                    @else
                        <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabel&task=edit&id=' . $item->ars_dlidlabel_id . '&' . JFactory::getSession()->getToken() . '=1&returnurl=' . $returnUrl) }}">
                        {{{ $item->label }}}
                        </a>
                    @endif

                </td>
                <td>
                    @include('site:com_ars/DownloadIDLabels/default_publish', ['item' => $item])
                </td>
                <td>
                    <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabels&task=reset&id=' . $item->ars_dlidlabel_id . '&' . JFactory::getSession()->getToken() . '=1&returnurl=' . $returnUrl) }}"
                       class="btn btn-warning" title="@lang('COM_ARS_DLIDLABELS_FIELD_RESET')">
                        <span class="icon icon-white icon-retweet"></span>
                    </a>
                    @unless($item->primary)
                    <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabels&task=remove&id=' . $item->ars_dlidlabel_id . '&' . JFactory::getSession()->getToken() . '=1&returnurl=' . $returnUrl) }}"
                       class="btn btn-danger" title="@lang('COM_ARS_DLIDLABELS_FIELD_TRASH')">
                        <span class="icon icon-white icon-trash"></span>
                    </a>
                    @endunless
                </td>
            </tr>
            @endforeach
        @else
            <tr>
                <td colspan="10">
                    @lang('COM_ARS_COMMON_NOITEMS_LABEL')
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</form>