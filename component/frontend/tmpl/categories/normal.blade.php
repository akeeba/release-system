<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Categories\Html $this */
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
    @if($this->params->get('show_page_heading'))
        <div class="page-header">
            <h1>{{{ $this->params->get('page_heading', $this->menu->title) }}}</h1>
        </div>
    @endif

    @include('site:com_ars/Categories/generic', ['section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL'])
</div>
