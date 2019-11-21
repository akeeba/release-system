<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Releases\Html $this */
/** @var  \Akeeba\ReleaseSystem\Site\Model\Releases $item */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;

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
		@jhtml('date', $released, \Joomla\CMS\Language\Text::_('DATE_FORMAT_LC1'))
		<button class="akeeba-btn--dark--small release-info-toggler" type="button"
				data-target="ars-release-{{{ $item->id }}}-info">
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
				<td>@jhtml('date', $released, \Joomla\CMS\Language\Text::_('DATE_FORMAT_LC1'))</td>
			</tr>
		@if($this->params->get('show_downloads', 1))
			<tr>
				<td>@lang('LBL_RELEASES_HITS')</td>
				<td>@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)</td>
			</tr>
		@endif
		</table>

		<div id=reltabs-{{ $item->id }}-notes"">
			<h3>
				@lang('COM_ARS_RELEASE_NOTES_LABEL')
			</h3>
			{{ \Akeeba\ReleaseSystem\Admin\Helper\Format::preProcessMessage($item->notes, 'com_ars.release_notes') }}
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
