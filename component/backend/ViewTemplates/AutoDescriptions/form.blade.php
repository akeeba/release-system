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

/** @var \Akeeba\ReleaseSystem\Admin\View\AutoDescriptions\Html $this */

defined('_JEXEC') or die;

HTMLHelper::_('formbehavior.chosen', '#environments');

/** @var \Akeeba\ReleaseSystem\Admin\Model\AutoDescriptions $item */
$item = $this->getItem();
?>

@extends('admin:com_ars/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <h3>@lang('COM_ARS_RELEASE_BASIC_LABEL')</h3>

            <div class="akeeba-form-group">
                <label for="category">@lang('LBL_AUTODESC_CATEGORY')</label>

                {{ Select::categories($item->category, 'category') }}
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

                {{ Select::environments('environments', $item->environments, ['multiple' => 'multiple'], 'environments[]') }}
            </div>

            <div class="akeeba-form-group">
                <label for="published">@lang('JPUBLISHED')</label>

                @jhtml('FEFHelper.select.booleanswitch', 'published', $item->published)
            </div>
        </div>

        <div>
            @jhtml('FEFHelper.edit.editor', 'description', $this->getItem()->description)
        </div>
    </div>
@stop