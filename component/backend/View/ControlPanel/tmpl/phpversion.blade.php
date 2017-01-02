<?php
/**
 * package   AkeebaReleaseSystem
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html  $this */

defined('_JEXEC') or die;
?>

@section('phpVersionWarning')
    @if (version_compare(PHP_VERSION, '5.5.0', 'lt'))
    <div id="phpVersionCheck" class="alert alert-warning">
        <h3>
            @lang('AKEEBA_COMMON_PHPVERSIONTOOOLD_WARNING_TITLE')
        </h3>
        <p>
            @sprintf('AKEEBA_COMMON_PHPVERSIONTOOOLD_WARNING_BODY',
                PHP_VERSION,
                $this->akeebaCommonDatePHP,
                $this->akeebaCommonDateObsolescence,
                '5.5'
            )
        </p>
    </div>
    @endif
@stop