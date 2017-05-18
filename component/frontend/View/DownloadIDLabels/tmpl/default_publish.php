<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels  $item  The model */

$task = $item->enabled ? 'unpublish' : 'publish';
$itemId = $this->input->getInt('Itemid') ? '&Itemid=' . $this->input->getInt('Itemid') : '';
$returnUrl = base64_encode(JUri::current());
$url = JRoute::_('index.php?option=com_ars&view=DownloadIDLabel&task=' . $task
                 . '&id=' . $item->ars_dlidlabel_id
                 . '&' . $this->container->platform->getToken(true) . '=1'
                 . '&returnurl=' . $returnUrl . $itemId);

if ($item->enabled)
{
	$btnStyle = 'btn-success';
	$btnIcon = 'icon-eye-open';
	$btnTitle = JText::_('JPUBLISHED');
}
else
{
	$btnStyle = 'btn-danger';
	$btnIcon = 'icon-eye-close';
	$btnTitle = JText::_('JUNPUBLISHED');
}

if ($item->primary): ?>
	<a class="btn btn-default" href="#" disabled="disabled" title="<?php echo $btnTitle ?>">
		<span class="icon icon-white icon-eye-open"></span>
	</a>
<?php else: ?>
	<a class="btn btn-default <?php echo $btnStyle ?>" href="<?php echo $url ?>" title="<?php echo $btnTitle ?>">
		<span class="icon icon-white <?php echo $btnIcon?>"></span>
	</a>
<?php
endif;