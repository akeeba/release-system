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

?>

<div class="ars-release-{{{ $item->id }}}">
	<h4 class="text-muted">
		{{{ $item->category->title }}}
		{{{ $item->version }}}
		<span class="label label-default">
			@lang('COM_ARS_RELEASES_MATURITY_' . $item->maturity)
		</span>
	</h4>
	<p class="text-muted">
		<strong>@lang('LBL_RELEASES_RELEASEDON')</strong>:
		@jhtml('date', $released, JText::_('DATE_FORMAT_LC2'))
	</p>
	<p>&nbsp;</p>
</div>