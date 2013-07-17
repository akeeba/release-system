<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$Itemid_query = empty($Itemid) ? "" : "&Itemid=$Itemid";
$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid_query);

?>
<tr>
	<td>
		<a href="<?php echo $download_url ?>" rel="nofollow">
			<?php echo $this->escape($item->title) ?>
		</a>
	</td>
	<td width="20%">
		<a href="<?php echo $download_url ?>" rel="nofollow" class="btn btn-small">
			<span class="icon icon-download"></span>
			<?php echo JText::_('LBL_ITEM_DOWNLOAD') ?>
		</a>
	</td>
	<td width="15%" class="small">
		<?php echo JText::_('LBL_ITEMS_HITS') ?>
		<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
	</td>
</tr>