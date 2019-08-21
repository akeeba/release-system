<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\VisualGroups\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $item */
$item = $this->getItem();
?>

@jhtml('formbehavior.chosen')

@extends('admin:com_ars/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="category_id">@lang('COM_ARS_RELEASES_FIELD_CATEGORY')</label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(), 'category_id', [
                    'id' => 'category_id', 'list.select' => $item->category_id,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="version">@lang('COM_ARS_RELEASES_FIELD_VERSION')</label>

                <input type="text" name="version" id="version" value="{{{ $item->version }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="alias">@lang('COM_ARS_RELEASES_FIELD_ALIAS')</label>

                <input type="text" name="alias" id="alias" value="{{{ $item->alias }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="maturity">@lang('COM_ARS_RELEASES_FIELD_MATURITY')</label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::maturity(true), 'maturity', [
                    'id' => 'maturity', 'list.select' => $item->maturity,
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="hits">@lang('JGLOBAL_HITS')</label>

                <input type="text" name="hits" id="hits" value="{{{ $item->hits }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="published">@lang('JPUBLISHED')</label>

                @jhtml('FEFHelper.select.booleanswitch', 'published', $item->published)
            </div>
        </div>

        <div>
            <div class="akeeba-form-group">
                <label for="access">@lang('JFIELD_ACCESS_LABEL')</label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel(true), 'access', [
                    'id' => 'access', 'list.select' => $item->access,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="show_unauth_links">@lang('COM_ARS_COMMON_SHOW_UNAUTH_LINKS')</label>

                @jhtml('FEFHelper.select.booleanswitch', 'show_unauth_links', $item->show_unauth_links)
            </div>

            <div class="akeeba-form-group">
                <label for="redirect_unauth">@lang('COM_ARS_COMMON_REDIRECT_UNAUTH')</label>

                <input type="text" name="redirect_unauth" id="redirect_unauth"
                       value="{{{ $item->redirect_unauth }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="groups">@lang('COM_ARS_COMMON_CATEGORIES_GROUPS_LABEL')</label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::subscriptionGroups(), 'groups[]', [
                    'id' => 'groups', 'list.select' => $item->groups,
                    'list.attr' => ['multiple' => 'multiple', 'class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="created">@lang('COM_ARS_RELEASES_FIELD_RELEASED')</label>

                @jhtml('calendar', $item->created, 'created', 'created')
            </div>

            <div class="akeeba-form-group">
                <label for="language">@lang('JFIELD_LANGUAGE_LABEL')</label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::languages(), 'language', [
                    'id' => 'language', 'list.select' => $item->language,
                ])
            </div>
        </div>
    </div>

    <div class="akeeba-container--50-50">
        <div>
            @jhtml('FEFHelper.edit.editor', 'notes', $this->getItem()->notes)
        </div>
    </div>
@stop