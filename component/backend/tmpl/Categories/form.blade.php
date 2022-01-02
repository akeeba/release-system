<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\Categories\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\Categories $item */
$item = $this->getItem();
?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <h3>
                @lang('COM_ARS_RELEASE_BASIC_LABEL')
            </h3>
            <div class="akeeba-form-group">
                <label for="title">@lang('COM_ARS_CATEGORIES_FIELD_TITLE')</label>

                <input type="text" name="title" id="title" value="{{{ $item->title }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="alias">@lang('COM_ARS_CATEGORIES_FIELD_ALIAS')</label>

                <input type="text" name="alias" id="alias" value="{{{ $item->alias }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="type">@lang('COM_ARS_CATEGORIES_FIELD_TYPE')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::categoryType(true), 'type', [
                    'id' => 'type', 'list.select' => $item->type,
                ])

            </div>

            <div class="akeeba-form-group">
                <label for="directory">@lang('COM_ARS_CATEGORIES_FIELD_DIRECTORY')</label>

                <input type="text" name="directory" id="directory" value="{{{ $item->directory }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="published">@lang('JPUBLISHED')</label>

                @jhtml('FEFHelp.select.booleanswitch', 'published', $item->published)
            </div>

            <div class="akeeba-form-group">
                <label for="access">@lang('JFIELD_ACCESS_LABEL')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel(true), 'access', [
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

                <input type="text" name="redirect_unauth" id="redirect_unauth"
                       value="{{{ $item->redirect_unauth }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="is_supported">@lang('COM_ARS_IS_SUPPORTED')</label>

                @jhtml('FEFHelp.select.booleanswitch', 'is_supported', $item->is_supported)
            </div>

            <div class="akeeba-form-group">
                <label for="language">@lang('JFIELD_LANGUAGE_LABEL')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::languages(), 'language', [
                    'id' => 'language', 'list.select' => $item->language,
                ])
            </div>
        </div>

        <div>
            @jhtml('FEFHelp.edit.editor', 'description', $this->getItem()->description)
        </div>
    </div>
@stop