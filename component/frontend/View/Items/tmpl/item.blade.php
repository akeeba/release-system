<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Items\Html $this */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Admin\Helper\Format;
use Akeeba\ReleaseSystem\Admin\Helper\Select;

$download_url =
		Router::_('index.php?option=com_ars&view=Item&task=download&format=raw&id=' . $item->id . '&Itemid=' . $this->Itemid);

if (!Filter::filterItem($item, false, $this->getContainer()->platform->getUser()->getAuthorisedViewLevels()) && !empty($item->redirect_unauth))
{
	$download_url = $item->redirect_unauth;
}

$directLink = false;

if ($this->directlink)
{
	$basename = ($item->type == 'file') ? $item->filename : $item->url;

	foreach ($this->directlink_extensions as $ext)
	{
		if (substr($basename, -strlen($ext)) == $ext)
		{
			$directLink = true;
			break;
		}
	}

	if ($directLink)
	{
		$directLinkURL = $download_url .
				(strstr($download_url, '?') !== false ? '&' : '?') .
				'dlid=' . $this->downloadId . '&jcompat=my' . $ext;
	}
}

if (!Filter::filterItem($item, false, $this->getContainer()->platform->getUser()->getAuthorisedViewLevels()) && !empty($item->redirect_unauth))
{
	$download_url = $item->redirect_unauth;
	$directLink = false;
}

$js = <<<JS
if (typeof(akeeba) == 'undefined')
{
	var akeeba = {};
}

if (typeof(akeeba.jQuery) === 'undefined')
{
	akeeba.jQuery = window.jQuery;
}

akeeba.jQuery(document).ready(function($){
    akeeba.fef.tabs();

    $('.release-info-toggler').off().on('click', function(){
        var target = $(this).data('target');
        $(target).slideToggle();
    })
});
JS;

$this->getContainer()->template->addJSInline($js);
?>

<div class="ars-item-{{{ $item->id }}}">
	<h4>
		<a href="{{ htmlentities($download_url) }}">
			{{{ $item->title }}}
		</a>
	</h4>
	@unless(empty($item->environments) || !$this->params->get('show_environments',1))
		<p>
			@foreach($item->environments as $environment)
				{{ Select::environmentIcon($environment) }}
			@endforeach
		</p>
	@endunless
	<p>
		<a href="{{ htmlentities($download_url) }}">
			<code>{{{ basename(($item->type == 'file') ? $item->filename : $item->url) }}}</code>
		</a>

		<a href="{{ htmlentities($download_url) }}" class="akeeba-btn--primary--small">
			<span class="akion-ios-cloud-download"></span>
			@lang('LBL_ITEM_DOWNLOAD')
		</a>

		<button class="akeeba-btn--dark--small release-info-toggler" type="button"
				data-target="#ars-item-{{{ $item->id }}}-info">
			<span class="akion-information-circled"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>

	<div id="ars-item-{{{ $item->id }}}-info" class="akeeba-panel--info" style="display: none">
		<table class="akeeba-table--striped ars-release-properties">
			@unless(!$this->params->get('show_downloads', 1))
				<tr>
					<td>
						@lang('LBL_ITEMS_HITS')
					</td>
					<td>
						@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)
					</td>
				</tr>
			@endunless

			@unless(empty($item->filesize) || !$this->params->get('show_filesize',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_FILESIZE')
					</td>
					<td>
						{{ Format::sizeFormat($item->filesize) }}
					</td>
				</tr>
			@endunless

			@unless(empty($item->md5) || !$this->params->get('show_md5',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_MD5')
					</td>
					<td>
						{{{ $item->md5 }}}
					</td>
				</tr>
			@endunless

			@unless(empty($item->sha1) || !$this->params->get('show_sha1',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_SHA1')
					</td>
					<td>
						{{{ $item->sha1 }}}
					</td>
				</tr>
			@endunless

			@unless(empty($item->sha256) || !$this->params->get('show_sha256',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_SHA256')
					</td>
					<td>
						{{{ $item->sha256 }}}
					</td>
				</tr>
			@endunless

			@unless(empty($item->sha384) || !$this->params->get('show_sha384',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_SHA384')
					</td>
					<td>
						{{{ $item->sha384 }}}
					</td>
				</tr>
			@endunless

			@unless(empty($item->sha512) || !$this->params->get('show_sha512',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_SHA512')
					</td>
					<td>
						{{{ $item->sha512 }}}
					</td>
				</tr>
			@endunless

			@unless(empty($item->environments) || !$this->params->get('show_environments',1))
				<tr>
					<td>
						@lang('LBL_ITEMS_ENVIRONMENTS')
					</td>
					<td>
						@foreach($item->environments as $environment)
							{{ Select::environmentIcon($environment) }}
						@endforeach
					</td>
				</tr>
			@endunless
		</table>

		@unless(empty($item->description))
			<p class="ars-item-description small">
				<?php echo Format::preProcessMessage($item->description, 'com_ars.item_description'); ?>
			</p>
		@endunless

		<div style="margin-top: 10px;">
			<p>
				<a href="{{ htmlentities($download_url) }}" class="akeeba-btn--primary">
					@lang('LBL_ITEM_DOWNLOAD')
				</a>
			</p>
			@unless(!$directLink)
				<a rel="nofollow" href="{{ htmlentities($directLinkURL) }}"
				   class="directlink hasTip" title="{{{ $this->directlink_description }}}">
					@lang('COM_ARS_LBL_ITEM_DIRECTLINK')
				</a>
			@endunless
		</div>
	</div>
</div>
