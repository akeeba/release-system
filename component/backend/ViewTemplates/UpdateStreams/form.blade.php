<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams $item */
$item = $this->getItem();

?>
@jhtml('formbehavior.chosen')

@extends('admin:com_ars/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="name">
                    @lang('LBL_UPDATES_NAME')
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
                    @lang('LBL_UPDATES_TYPE')
                </label>

                {{ \Akeeba\ReleaseSystem\Admin\Helper\Select::updateTypes('type', $item->type) }}
            </div>

            <div class="akeeba-form-group">
                <label for="category">
                    @lang('COM_ARS_RELEASES_FIELD_CATEGORY')
                </label>

                @jhtml('FEFHelper.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(), 'category', [
                    'id' => 'category', 'list.select' => $item->category,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="packname">
                    @lang('LBL_UPDATES_PACKNAME')
                </label>

                <input type="text" name="packname" id="packname" value="{{{ $item->packname }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="element">
                    @lang('LBL_UPDATES_ELEMENT')
                </label>

                <input type="text" name="element" id="element" value="{{{ $item->element }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="client_id">
                    @lang('LBL_RELEASES_CLIENT_ID')
                </label>

                {{ \Akeeba\ReleaseSystem\Admin\Helper\Select::client_id('client_id', $item->client_id) }}
            </div>

            <div class="akeeba-form-group">
                <label for="folder">
                    @lang('LBL_UPDATES_FOLDER')
                </label>

                <input type="text" name="folder" id="folder" value="{{{ $item->folder }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="jedid">
                    @lang('LBL_UPDATES_JEDID')
                </label>

                <input type="text" name="jedid" id="jedid" value="{{{ $item->jedid }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="published">
                    @lang('JPUBLISHED')
                </label>

                @jhtml('FEFHelper.select.booleanswitch', 'published', $item->published)
            </div>
        </div>
    </div>
@stop