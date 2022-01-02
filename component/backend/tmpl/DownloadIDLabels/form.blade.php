<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Form\Field\UserField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\DownloadIDLabels\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $item */
$item = $this->getItem();
?>

@extends('any:lib_fof40/Common/edit')

@section('edit-form-body')
    <div class="akeeba-container--50-50">
        <div>
            <div class="akeeba-form-group">
                <label for="label">
                    @lang('COM_ARS_DLIDLABELS_FIELD_LABEL')
                </label>

                <input type="text" name="label" id="label" value="{{{ $item->label }}}" />
            </div>

            <div class="akeeba-form-group">
                <label for="label">
                    @lang('COM_ARS_DLIDLABELS_FIELD_USER_ID')
                </label>

                @include('any:lib_fof40/Common/user_select', ['field' => 'user_id', 'item' => $item, 'required' => true])
            </div>

            <div class="akeeba-form-group">
                <label for="title">
                    @lang('JPUBLISHED')
                </label>

                @jhtml('FEFHelp.select.booleanswitch', 'enabled', $item->enabled)
            </div>
        </div>
    </div>
@stop
