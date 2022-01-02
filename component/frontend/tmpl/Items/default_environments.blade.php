<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\Items $model */

?>
@unless(empty($fieldValue))
    @foreach ($fieldValue as $environment)
        <span class="akeeba-label--teal ars-environment-icon">{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::environmentTitle((int)$environment) }}</span>
    @endforeach
@endunless
