<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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
		<span class="glyphicon glyphicon-file"></span>
		<a href="{{ htmlentities($download_url) }}">
			<code>{{{ basename(($item->type == 'file') ? $item->filename : $item->url) }}}</code>
		</a>

		<a href="{{ htmlentities($download_url) }}" class="btn btn-link">
			<span class="glyphicon glyphicon-download-alt"></span>
			@lang('LBL_ITEM_DOWNLOAD')
		</a>

		<button class="btn btn-link" type="button" data-toggle="collapse"
				data-target="#ars-item-{{{ $item->id }}}-info" aria-expanded="false"
				aria-controls="ars-item-{{{ $item->id }}}-info">
			<span class="glyphicon glyphicon-info-sign"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>
	<p>&nbsp;</p>

	<div id="ars-item-{{{ $item->id }}}-info" class="well collapse">
		<dl class="dl-horizontal ars-release-properties">
			@unless(!$this->params->get('show_downloads', 1))
				<dt>
					@lang('LBL_ITEMS_HITS')
				</dt>
				<dd>
					@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)
				</dd>
			@endunless

			@unless(empty($item->filesize) || !$this->params->get('show_filesize',1))
				<dt>
					@lang('LBL_ITEMS_FILESIZE')
				</dt>
				<dd>
					{{ Format::sizeFormat($item->filesize) }}
				</dd>
			@endunless

			@unless(empty($item->md5) || !$this->params->get('show_md5',1))
				<dt>
					@lang('LBL_ITEMS_MD5')
				</dt>
				<dd>
					{{{ $item->md5 }}}
				</dd>
			@endunless

			@unless(empty($item->sha1) || !$this->params->get('show_sha1',1))
				<dt>
					@lang('LBL_ITEMS_SHA1')
				</dt>
				<dd>
					{{{ $item->sha1 }}}
				</dd>
			@endunless

			@unless(empty($item->environments) || !$this->params->get('show_environments',1))
				<dt>
					@lang('LBL_ITEMS_ENVIRONMENTS')
				</dt>
				<dd>
					@foreach($item->environments as $environment)
						{{ Select::environmentIcon($environment) }}
					@endforeach
				</dd>
			@endunless
		</dl>

		@unless(empty($item->description))
			<div class="ars-item-description well small">
				<?php echo Format::preProcessMessage($item->description, 'com_ars.item_description'); ?>
			</div>
		@endunless

		<div>
			<div class="pull-left">
				<p class="readmore">
					<a href="{{ htmlentities($download_url) }}" class="btn btn-primary">
						@lang('LBL_ITEM_DOWNLOAD')
					</a>
				</p>
			</div>
			@unless(!$directLink)
				<div class="pull-left">
					&nbsp;
					<a rel="nofollow" href="{{ htmlentities($directLinkURL) }}"
					   class="directlink hasTip" title="{{{ $this->directlink_description }}}">
						@lang('COM_ARS_LBL_ITEM_DIRECTLINK')
					</a>
				</div>
			@endunless
			<div class="clearfix"></div>
		</div>
	</div>
</div>