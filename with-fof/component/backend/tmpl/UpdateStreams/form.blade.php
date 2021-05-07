<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Component\ARS\Administrator\Helper\Select;

/** @var \Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams $item */
$item = $this->getItem();

?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="name">
                    @lang('COM_ARS_STREAM_FIELD_NAME')
                </label>

                <input type="text" name="name" id="name" value="{{{ $item->name }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="alias">
                    @lang('JFIELD_ALIAS_LABEL')
                </label>

                <input type="text" name="alias" id="alias" value="{{{ $item->alias }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="type">
                    @lang('COM_ARS_STREAM_FIELD_TYPE')
                </label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::updateTypes(true), 'type', [
                    'id' => 'type', 'list.select' => $item->type
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="category">
                    @lang('COM_ARS_RELEASES_FIELD_CATEGORY')
                </label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::categories(), 'category', [
                    'id' => 'category', 'list.select' => $item->category,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="packname">
                    @lang('COM_ARS_STREAM_FIELD_PACKNAME')
                </label>

                <input type="text" name="packname" id="packname" value="{{{ $item->packname }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="element">
                    @lang('COM_ARS_STREAM_FIELD_ELEMENT')
                </label>

                <input type="text" name="element" id="element" value="{{{ $item->element }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="client_id">
                    @lang('COM_ARS_STREAM_FIELD_CLIENTID_LBL')
                </label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::client_id(), 'client_id', [
                    'id' => 'category', 'list.select' => $item->client_id
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="folder">
                    @lang('COM_ARS_STREAM_FIELD_FOLDER')
                </label>

                <input type="text" name="folder" id="folder" value="{{{ $item->folder }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="jedid">
                    @lang('COM_ARS_STREAM_FIELD_JEDID')
                </label>

                <input type="text" name="jedid" id="jedid" value="{{{ $item->jedid }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="published">
                    @lang('JPUBLISHED')
                </label>

                @jhtml('FEFHelp.select.booleanswitch', 'published', $item->published)
            </div>
        </div>
    </div>
@stop