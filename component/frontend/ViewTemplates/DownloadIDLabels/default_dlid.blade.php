<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $item The model */

?>
@if ($item->primary)
    <span class="akeeba-label--grey">
		{{ $item->dlid }}
	</span>
@else
    {{ $item->user_id }}:{{ $item->dlid }}
@endif
