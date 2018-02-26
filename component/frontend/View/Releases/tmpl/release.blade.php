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
		<button class="akeeba-btn--dark--small release-info-toggler" type="button"
				data-target="#ars-release-{{{ $item->id }}}-info">
			<span class="akion-information-circled"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>

	<div id="ars-release-{{{ $item->id }}}-info" class="akeeba-panel--info" style="display: none;">
		<table class="ars-release-properties akeeba-table--striped" style="margin-bottom:15px">
			<tr>
				<td>@lang('COM_ARS_RELEASES_FIELD_MATURITY')</td>
				<td>@lang('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity))</td>
			</tr>
			<tr>
				<td>@lang('LBL_RELEASES_RELEASEDON')</td>
				<td>@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))</td>
			</tr>
		@if($this->params->get('show_downloads', 1))
			<tr>
				<td>@lang('LBL_RELEASES_HITS')</td>
				<td>@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)</td>
			</tr>
		@endif
		</table>

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
			<p style="margin-top: 15px;">
				<a href="{{ htmlentities($release_url) }}" class="akeeba-btn--primary">
					@lang('LBL_RELEASE_VIEWITEMS')
				</a>
			</p>
		@endif
	</div>
</div>
