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

$released = JFactory::getDate($item->created);

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

<div class="ars-release-{{{ $item->id }}} well">
	<h4>
		<span class="label {{{ $maturityClass }}} pull-right">
			@lang('COM_ARS_RELEASES_MATURITY_' . $item->maturity)
		</span>

		<a href="{{ htmlentities($release_url) }}">
			{{{ $item->version }}}
		</a>
	</h4>

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