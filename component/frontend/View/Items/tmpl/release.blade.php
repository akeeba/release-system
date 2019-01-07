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
use Akeeba\ReleaseSystem\Admin\Helper\Format;

$released = $this->container->platform->getDate($item->created);

?>

<div class="ars-release-{{{ $item->id }}} ars-release-{{ $this->release->category->is_supported ? 'supported' : 'unsupported' }}">
	<h4 class="text-muted">
		{{{ $item->category->title }}}
		{{{ $item->version }}}
		<span class="akeeba-label--grey--small">
			@lang('COM_ARS_RELEASES_MATURITY_' . $item->maturity)
		</span>
	</h4>
	<p class="text-muted">
		<strong>@lang('LBL_RELEASES_RELEASEDON')</strong>:
		@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))

		<button class="akeeba-btn--dark--small release-info-toggler" type="button"
				data-target="#ars-release-{{{ $item->id }}}-info">
			<span class="akion-information-circled"></span>
			@lang('COM_ARS_RELEASES_MOREINFO')
		</button>
	</p>

	<div id="ars-release-{{{ $item->id }}}-info" class="akeeba-panel--info" style="display: none;">
		{{ Format::preProcessMessage($item->notes, 'com_ars.release_notes') }}
	</div>
</div>
