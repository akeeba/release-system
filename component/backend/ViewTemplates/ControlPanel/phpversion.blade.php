<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

defined('_JEXEC') or die;
?>

@section('phpVersionWarning')
    {{-- Old PHP version reminder --}}
    @include('admin:com_ars/Common/phpversion_warning', [
        'softwareName'  => 'Akeeba Release System',
        'minPHPVersion' => '7.3.0',
    ])
@stop
