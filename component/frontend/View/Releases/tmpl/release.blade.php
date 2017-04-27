<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Releases\Html $this */

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
		$maturityClass = 'label-success';
		break;

	case 'rc':
		$maturityClass = 'label-info';
		break;

	case 'beta':
		$maturityClass = 'label-warning';
		break;

	case 'alpha':
		$maturityClass = 'label-important';
		break;

	default:
		$maturityClass = 'label-inverse';
		break;
}

?>

<div class="ars-release-{{{ $item->id }}}">
	<h4>
		<a href="{{ htmlentities($release_url) }}">
			@lang('COM_ARS_RELEASES_VERSION')
			{{{ $item->version }}}
		</a>
		<span class="label {{{ $maturityClass }}}">
			@lang('COM_ARS_RELEASES_MATURITY_' . $item->maturity)
		</span>
	</h4>
	<p>
		<strong>@lang('LBL_RELEASES_RELEASEDON')</strong>:
		@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
		<button class="btn btn-link" type="button" data-toggle="collapse"
				data-target="#ars-release-{{{ $item->id }}}-info" aria-expanded="false"
				aria-controls="ars-release-{{{ $item->id }}}-info">
			<span class="glyphicon glyphicon-info-sign"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>
	<p>&nbsp;</p>

	<div id="ars-release-{{{ $item->id }}}-info" class="well collapse">
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

		@jhtml('bootstrap.startTabSet', 'ars-reltabs-' . $item->id, ['active' => "reltabs-{$item->id}-desc"])

		@jhtml('bootstrap.addTab', 'ars-reltabs-' . $item->id, "reltabs-{$item->id}-desc", JText::_('COM_ARS_RELEASE_DESCRIPTION_LABEL'))
		{{ Format::preProcessMessage($item->description, 'com_ars.release_description') }}
		@jhtml('bootstrap.endTab')

		@jhtml('bootstrap.addTab', 'ars-reltabs-' . $item->id, "reltabs-{$item->id}-notes", JText::_('COM_ARS_RELEASE_NOTES_LABEL'))
		{{ Format::preProcessMessage($item->notes, 'com_ars.release_notes') }}
		@jhtml('bootstrap.endTab')

		@jhtml('bootstrap.endTabSet')

		@if(!isset($no_link) || !$no_link)
			<p class="readmore">
				<a href="{{ htmlentities($release_url) }}" class="btn btn-primary">
					@lang('LBL_RELEASE_VIEWITEMS')
				</a>
			</p>
		@endif
	</div>
</div>