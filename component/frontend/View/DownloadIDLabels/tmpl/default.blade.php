<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
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

<div class="akeeba-block--info">
    @sprintf('COM_ARS_DLIDLABELS_MASTERDLID', Filter::myDownloadID())
</div>

<form name="arsDownloadID" action="{{ htmlentities($formURI) }}" method="post" class="akeeba-form--inline">
        <div class="akeeba-form-group">
            <label for="enabled">
                @lang('JSEARCH_FILTER_LABEL')
            </label>
            @jhtml('select.genericlist', $options, 'enabled', ['onchange' => 'this.form.submit()', 'class' => 'form-control'], 'value', 'text', $this->getModel()->getState('enabled'), false, true)
        </div>

        <div class="akeeba-form-group">
            <input type="text" name="label" value="{{ $this->getModel()->getState('label') }}"
                   placeholder="@lang('COM_ARS_DLIDLABELS_FIELD_LABEL')" />
        </div>

    <div class="akeeba-form-group--actions">
        <button type="submit" class="akeeba-btn--primary--small">
            <span class="akion-search"></span>
        </button>

        <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabel&task=add&' . $this->container->platform->getToken(true) . '=1&returnurl=' . $returnUrl) }}"
           class="akeeba-btn--primary--small">
            <span class="akion-plus-circled"></span>
            @lang('JNEW')
        </a>
    </div>

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
                        <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabel&task=edit&id=' . $item->ars_dlidlabel_id . '&' . $this->container->platform->getToken(true) . '=1&returnurl=' . $returnUrl) }}">
                        {{{ $item->label }}}
                        </a>
                    @endif

                </td>
                <td>
                    @include('site:com_ars/DownloadIDLabels/default_publish', ['item' => $item])
                </td>
                <td>
                    <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabels&task=reset&id=' . $item->ars_dlidlabel_id . '&' . $this->container->platform->getToken(true) . '=1&returnurl=' . $returnUrl) }}"
                       class="akeeba-btn--orange--small" title="@lang('COM_ARS_DLIDLABELS_FIELD_RESET')">
                        <span class="akion-refresh"></span>
                    </a>
                    @unless($item->primary)
                    <a href="{{ JRoute::_('index.php?option=com_ars&view=DownloadIDLabels&task=remove&id=' . $item->ars_dlidlabel_id . '&' . $this->container->platform->getToken(true) . '=1&returnurl=' . $returnUrl) }}"
                       class="akeeba-btn--red--small" title="@lang('COM_ARS_DLIDLABELS_FIELD_TRASH')">
                        <span class="akion-trash-b"></span>
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

    <input type="hidden" name="task" value="browse" />
</form>
