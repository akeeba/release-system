<?php
defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Categories\Html $this */

use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Admin\Helper\Format;

$category_url = Router::_('index.php?option=com_ars&view=Releases&category_id=' . $item->id . '&Itemid=' . $Itemid);

if (!Filter::filterItem($item, false, $this->getContainer()->platform->getUser()->getAuthorisedViewLevels()) && !empty($item->redirect_unauth))
{
	$category_url = $item->redirect_unauth;
}
?>
<div class="ars-category-{{{ $id }}}">

	<h4 class="text-muted">
		{{{ $item->title }}}
	</h4>
</div>
<div class="clearfix"></div>