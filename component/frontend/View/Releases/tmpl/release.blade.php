<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Releases\Html $this */
/** @var  \Akeeba\ReleaseSystem\Site\Model\Releases $item */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Admin\Helper\Format;

$released = $this->container->platform->getDate($item->created);

$release_url = Router::_('index.php?option=com_ars&view=Items&release_id=' . $item->id . '&Itemid=' . $Itemid);

$authorisedViewLevels = $this->getContainer()->platform->getUser()->getAuthorisedViewLevels();

if (!Filter::filterItem($item, false, $authorisedViewLevels) && !empty($item->redirect_unauth))
{
	$release_url = $item->redirect_unauth;
}

switch ($item->maturity)
{
	case 'stable':
		$maturityClass = 'akeeba-label--green--small';
		break;

	case 'rc':
		$maturityClass = 'akeeba-label--teal--small';
		break;

	case 'beta':
		$maturityClass = 'akeeba-label--orange--small';
		break;

	case 'alpha':
		$maturityClass = 'akeeba-label--red--small';
		break;

	default:
		$maturityClass = 'akeeba-label--dark--small';
		break;
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

akeeba.jQuery(document).ready(function(){
    akeeba.fef.tabs();
});
JS;

$this->getContainer()->template->addJSInline($js);

?>

<div class="ars-release-{{{ $item->id }}}">
	<h4>
		<a href="{{ htmlentities($release_url) }}">
			@lang('COM_ARS_RELEASES_VERSION')
			{{{ $item->version }}}
		</a>
		<span class="{{{ $maturityClass }}}">
			@lang('COM_ARS_RELEASES_MATURITY_' . $item->maturity)
		</span>
	</h4>
	<p>
		<strong>@lang('LBL_RELEASES_RELEASEDON')</strong>:
		@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
		<button class="akeeba-btn--dark--small" type="button" data-toggle="collapse"
				data-target="#ars-release-{{{ $item->id }}}-info" aria-expanded="false"
				aria-controls="ars-release-{{{ $item->id }}}-info">
			<span class="akion-information-circled"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>
	<p>&nbsp;</p>

	<div id="ars-release-{{{ $item->id }}}-info" class="akeeba-panel--info collapse">
		<dl class="dl-horizontal ars-release-properties">
			<dt>
				@lang('COM_ARS_RELEASES_FIELD_MATURITY')
			</dt>
			<dd>
				@lang('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity))
			</dd>

			<dt>
				@lang('LBL_RELEASES_RELEASEDON')
			</dt>
			<dd>
				@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
			</dd>

			@if($this->params->get('show_downloads', 1))
				<dt>
					@lang('LBL_RELEASES_HITS')
				</dt>
				<dd>
					@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)
				</dd>
			@endif
		</dl>

		<div class="akeeba-tabs">
			<label for="reltabs-{{ $item->id }}-desc" class="active">
				@lang('COM_ARS_RELEASE_DESCRIPTION_LABEL')
			</label>

			<section id="reltabs-{{ $item->id }}-desc">
				{{ Format::preProcessMessage($item->description, 'com_ars.release_description') }}
			</section>

			<label for="reltabs-{{ $item->id }}-notes">
				@lang('COM_ARS_RELEASE_NOTES_LABEL')
			</label>

			<section id=reltabs-{{ $item->id }}-notes"">
				{{ Format::preProcessMessage($item->notes, 'com_ars.release_notes') }}
			</section>
		</div>

		@if(!isset($no_link) || !$no_link)
			<p style="margin-top: 10px;">
				<a href="{{ htmlentities($release_url) }}" class="akeeba-btn--primary">
					@lang('LBL_RELEASE_VIEWITEMS')
				</a>
			</p>
		@endif
	</div>
</div>
