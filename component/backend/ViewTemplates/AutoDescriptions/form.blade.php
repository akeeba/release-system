<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\HTML\HTMLHelper;

/** @var \Akeeba\ReleaseSystem\Admin\View\AutoDescriptions\Html $this */

defined('_JEXEC') or die;

HTMLHelper::_('formbehavior.chosen', '#environments');

/** @var \Akeeba\ReleaseSystem\Admin\Model\AutoDescriptions $item */
$item = $this->getItem();
?>
@jhtml('formbehavior.chosen')

@extends('any:lib_fof40/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <h3>@lang('COM_ARS_RELEASE_BASIC_LABEL')</h3>

            <div class="akeeba-form-group">
                <label for="category">@lang('LBL_AUTODESC_CATEGORY')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(), 'category', [
                    'id' => 'category', 'list.select' => $item->category,
                    'list.attr' => ['class' => 'advancedSelect']
                ])
            </div>

            <div class="akeeba-form-group">
                <label for="packname">@lang('LBL_AUTODESC_PACKNAME')</label>

                <input type="text" name="packname" id="packname" value="{{{ $item->packname }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="title">@lang('LBL_AUTODESC_TITLE')</label>

                <input type="text" name="title" id="title" value="{{{ $item->title }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="environments">@lang('LBL_ITEMS_ENVIRONMENTS')</label>

                @jhtml('FEFHelp.select.genericlist',
                    \Akeeba\ReleaseSystem\Admin\Helper\Select::environments(), 'environments[]', [
                        'id' => 'environments', 'list.select' => $item->environments,
                        'list.attr' => ['multiple' => 'multiple', 'class' => 'advancedSelect']
                    ])
            </div>

            <div class="akeeba-form-group">
                <label for="published">@lang('JPUBLISHED')</label>

                @jhtml('FEFHelp.select.booleanswitch', 'published', $item->published)
            </div>
        </div>

        <div>
            @jhtml('FEFHelp.edit.editor', 'description', $this->getItem()->description)
        </div>
    </div>
@stop