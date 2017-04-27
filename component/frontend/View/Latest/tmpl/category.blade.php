<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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

<div class="ars-category-{{{ $item->id }}} well">
	<h4 class="{{ $item->type == 'bleedingedge' ? 'warning' : '' }}">
		<span class="label {{{ $maturityClass }}} pull-right">
			@lang('COM_ARS_RELEASES_MATURITY_' . $release->maturity)
		</span>

		<a href="{{ htmlentities($release_url) }}">
			{{{ $item->title }}} {{{ $release->version }}}
		</a>
	</h4>

	<div class="ars-latest-category">
		<div class="ars-category-description">
			{{ Format::preProcessMessage($item->description, 'com_ars.category_description') }}
		</div>
	</div>

	<dl class="dl-horizontal ars-release-properties">
		<dt>
			@lang('COM_ARS_RELEASES_FIELD_MATURITY')
		</dt>
		<dd>
			@lang('COM_ARS_RELEASES_MATURITY_'.  strtoupper($release->maturity))
		</dd>

		<dt>
			@lang('LBL_RELEASES_RELEASEDON')
		</dt>
		<dd>
			@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
		</dd>
	</dl>

	<table class="table table-striped">
		@foreach($release->items->sortBy($this->params->get('items_orderby', 'ordering'))->filter(function ($item)
		{
			return \Akeeba\ReleaseSystem\Site\Helper\Filter::filterItem($item, true);
		}) as $i)
		@include('site:com_ars/Latest/item', ['item' => $i, 'Itemid' => $this->Itemid])
		@endforeach
	</table>

	<p class="readmore">
		<a href="{{ htmlentities($release_url) }}">
			@lang('LBL_RELEASE_VIEWITEMS')
		</a>
	</p>
</div>
