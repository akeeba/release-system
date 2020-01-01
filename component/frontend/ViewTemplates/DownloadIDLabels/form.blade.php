<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\ReleaseSystem\Admin\View\DownloadIDLabels\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $item */
$item = $this->getItem();

?>
@extends('admin:com_ars/Common/edit')

@section('edit-page-top')
    <h3>@lang('COM_ARS_DLIDLABELS_GROUP_BASIC')</h3>
@stop

@section('edit-form-body')
    <div class="akeeba-form-group">
        <label for="label">
            @lang('COM_ARS_DLIDLABELS_FIELD_LABEL')
        </label>

        <input type="text" name="label" id="label" value="{{ $item->label }}" />
    </div>

    <div class="akeeba-form-group">
        <label for="title">
            @lang('JPUBLISHED')
        </label>

        @jhtml('FEFHelper.select.booleanswitch', 'enabled', $item->enabled)
    </div>

    <div class="akeeba-form-group--pull-right">
        <div class="akeeba-form-group--actions">
            <button type="submit" class="akeeba-btn" onclick="this.form.task.value='save'; this.form.submit();">
                Submit
            </button>
        </div>
    </div>
@stop