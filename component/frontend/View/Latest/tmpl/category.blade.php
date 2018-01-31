<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Latest\Html $this */
/** @var  \Akeeba\ReleaseSystem\Site\Model\Categories $item */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Admin\Helper\Format;

// Do I have a release?
if (!isset($this->releases[$item->id]))
{
	return;
}

/** @var \Akeeba\ReleaseSystem\Site\Model\Releases $release */
$release = $this->releases[$item->id];
$released = $this->container->platform->getDate($release->created);
$release_url = Router::_('index.php?option=com_ars&view=Items&release_id=' . $release->id . '&Itemid=' . $Itemid);
$authorisedViewLevels = $this->getContainer()->platform->getUser()->getAuthorisedViewLevels();

if (!Filter::filterItem($release, false, $authorisedViewLevels) && !empty($release->redirect_unauth))
{
	$release_url = $release->redirect_unauth;
}

switch ($release->maturity)
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

<div class="ars-category-{{{ $item->id }}} ars-category-{{ $item->is_supported ? 'supported' : 'unsupported' }}">
	<h4 class="{{ $item->type == 'bleedingedge' ? 'warning' : '' }}">
		<a href="{{ htmlentities($release_url) }}">
			{{{ $item->title }}} {{{ $release->version }}}
		</a>

		<span class="{{{ $maturityClass }}}">
			@lang('COM_ARS_RELEASES_MATURITY_' . $release->maturity)
		</span>
	</h4>

	<div class="ars-latest-category">
		<div class="ars-category-description">
			{{ Format::preProcessMessage($item->description, 'com_ars.category_description') }}
		</div>
	</div>

	<table class="akeeba-table--striped">
		<tr>
			<td>
				@lang('COM_ARS_RELEASES_FIELD_MATURITY')
			</td>
			<td colspan="2">
				@lang('COM_ARS_RELEASES_MATURITY_'.  strtoupper($release->maturity))
			</td>
		</tr>
		<tr>
			<td>
				@lang('LBL_RELEASES_RELEASEDON')
			</td>
			<td colspan="2">
				@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
			</td>
		</tr>

		@foreach($release->items->sortBy($this->params->get('items_orderby', 'ordering'))->filter(function ($item)
		{
			return \Akeeba\ReleaseSystem\Site\Helper\Filter::filterItem($item, true);
		}) as $i)
		@include('site:com_ars/Latest/item', ['item' => $i, 'Itemid' => $this->Itemid])
		@endforeach
	</table>

	<p style="margin-top:15px">
		<a href="{{ htmlentities($release_url) }}" class="akeeba-btn--primary">
			@lang('LBL_RELEASE_VIEWITEMS')
		</a>
	</p>
</div>
