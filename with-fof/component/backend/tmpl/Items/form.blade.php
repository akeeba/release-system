<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\Items\Html $this */

/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $item */
$item = $this->getItem();
?>
@js('media://com_ars/js/gui-helpers.js', $this->getContainer()->mediaVersion)
@js('media://com_ars/js/Items.js', $this->getContainer()->mediaVersion)

@extends('any:lib_fof40/Common/edit')

@jhtml('behavior.core')
@jhtml('formbehavior.chosen')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="release_id">@lang('COM_ARS_ITEM_FIELD_RELEASE')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::releases(true), 'release_id', [
                    'id' => 'release_id', 'list.select' => $item->release_id,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="title">@lang('COM_ARS_ITEM_FIELD_TITLE')</label>

                <input type="text" name="title" id="title" value="{{{ $item->title }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="alias">@lang('COM_ARS_RELEASES_FIELD_ALIAS')</label>

                <input type="text" name="alias" id="alias" value="{{{ $item->alias }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="type">@lang('COM_ARS_ITEM_FIELD_TYPE')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::itemType(true), 'type', [
                    'id' => 'type', 'list.select' => $item->type,
                    'list.attr' => ['onchange' => 'arsItems.onTypeChange();']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="filename">@lang('COM_ARS_ITEM_FIELD_FILE')</label>

                <select id="filename" name="filename"></select>
            </div>

            <div class="akeeba-form-group">
                <label for="url">@lang('COM_ARS_ITEM_FIELD_LINK')</label>

                <input type="text" name="url" id="url" value="{{{ $item->url }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="filesize">@lang('COM_ARS_ITEM_FIELD_FILESIZE')</label>

                <input type="text" name="filesize" id="filesize" value="{{{ $item->filesize }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="md5">@lang('COM_ARS_ITEM_FIELD_MD5')</label>

                <input type="text" name="md5" id="md5" value="{{{ $item->md5 }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="sha1">@lang('COM_ARS_ITEM_FIELD_SHA1')</label>

                <input type="text" name="sha1" id="sha1" value="{{{ $item->sha1 }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="sha256">@lang('COM_ARS_ITEM_FIELD_SHA256')</label>

                <input type="text" name="sha256" id="sha256" value="{{{ $item->sha256 }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="sha384">@lang('COM_ARS_ITEM_FIELD_SHA384')</label>

                <input type="text" name="sha384" id="sha384" value="{{{ $item->sha384 }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="sha512">@lang('COM_ARS_ITEM_FIELD_SHA512')</label>

                <input type="text" name="sha512" id="sha512" value="{{{ $item->sha512 }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="hits">@lang('JGLOBAL_HITS')</label>

                <input type="text" name="hits" id="hits" value="{{{ $item->hits }}}" />
            </div>
        </div>

        <div>
            <div class="akeeba-form-group">
                <label for="published">@lang('JPUBLISHED')</label>

                @jhtml('FEFHelp.select.booleanswitch', 'published', $item->published)
            </div>

            <div class="akeeba-form-group">
                <label for="access">@lang('JFIELD_ACCESS_LABEL')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::accessLevel(true), 'access', [
                    'id' => 'access', 'list.select' => $item->access,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="show_unauth_links">@lang('COM_ARS_COMMON_SHOW_UNAUTH_LINKS')</label>

                @jhtml('FEFHelp.select.booleanswitch', 'show_unauth_links', $item->show_unauth_links)
            </div>

            <div class="akeeba-form-group">
                <label for="redirect_unauth">@lang('COM_ARS_COMMON_REDIRECT_UNAUTH')</label>

                <input type="text" name="redirect_unauth" id="redirect_unauth" value="{{{ $item->redirect_unauth }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="environments">@lang('COM_ARS_ITEM_FIELD_ENVIRONMENTS')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::environments(), 'environments[]', [
                    'id' => 'environments', 'list.select' => $item->environments,
                    'list.attr' => ['multiple' => 'multiple', 'class' => 'advancedSelect']
                ])

            </div>

            <div class="akeeba-form-group">
                <label for="updatestream">@lang('COM_ARS_ITEM_FIELD_UPDATESTREAM')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::updateStreams(true), 'updatestream', [
                    'id' => 'updatestream', 'list.select' => $item->updatestream,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="language">@lang('JFIELD_LANGUAGE_LABEL')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\Component\ARS\Administrator\Helper\Select::languages(), 'language', [
                    'id' => 'language', 'list.select' => $item->language,
                ])
            </div>
        </div>
    </div>

    <div class="akeeba-container--100">
        <div>
            @jhtml('FEFHelp.edit.editor', 'description', $this->getItem()->description)
        </div>
    </div>
@stop
