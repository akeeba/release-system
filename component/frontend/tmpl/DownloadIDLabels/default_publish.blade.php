<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $item The model */

$task      = $item->enabled ? 'unpublish' : 'publish';
$itemId    = $this->input->getInt('Itemid') ? '&Itemid=' . $this->input->getInt('Itemid') : '';
$returnUrl = base64_encode(Uri::getInstance()->toString());
$url       = Route::_('index.php?option=com_ars&view=DownloadIDLabel&task=' . $task
                 . '&id=' . $item->ars_dlidlabel_id
                 . '&' . $this->container->platform->getToken(true) . '=1'
                 . '&returnurl=' . $returnUrl . $itemId);
?>

@if ($item->primary)
    <a class="akeeba-btn--grey--small" href="#" disabled="disabled" title="@lang('JPUBLISHED')">
        <span class="akion-checkmark"></span>
    </a>
@elseif($item->enabled)
    <a class="akeeba-btn--green--small" title="@lang('JPUBLISHED')" href="{{ $url }}">
		<span class="akion-checkmark"></span>
	</a>
@else
    <a class="akeeba-btn--red--small" title="@lang('JUNPUBLISHED')" href="{{ $url }}">
        <span class="akion-close"></span>
	</a>
@endif
