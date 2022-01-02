<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\Environments\Html $this */

defined('_JEXEC') or die;

?>

@extends('any:lib_fof40/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="title">@lang('LBL_ENVIRONMENTS_TITLE')</label>

                <input type="text" name="title" id="title" value="{{{ $this->item->title }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="xmltitle">@lang('LBL_ENVIRONMENT_XMLTITLE')</label>

                <input type="text" name="xmltitle" id="xmltitle" value="{{{ $this->item->xmltitle }}}" />
                <span>@lang('LBL_ENVIRONMENT_XMLTITLE_TIP')</span>
            </div>
        </div>
    </div>
@stop