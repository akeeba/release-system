<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Latest\Html $this */

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
?>

<tr>
	<td>
		<a href="{{ htmlentities($download_url) }}" rel="nofollow">
			{{{ $item->title }}}
		</a>
	</td>
	<td width="25%">
		<a href="{{ htmlentities($download_url) }}" rel="nofollow" class="akeeba-btn--primary--small">
			<span class="icon icon-download"></span>
			@lang('LBL_ITEM_DOWNLOAD')
		</a>
	</td>
	<td width="20%" class="small">
		@unless(!$this->cparams->get('show_downloads', 1))
			@lang('LBL_ITEMS_HITS')
			@sprintf(($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits)
		@endunless
	</td>
</tr>
