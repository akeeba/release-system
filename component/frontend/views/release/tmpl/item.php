<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$Itemid = JRequest::getInt('Itemid',0);
$Itemid = empty($Itemid) ? "" : "&Itemid=$Itemid";
$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid);

?>
<div class="ars-browse-items">
	<div class="ars-item-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_ITEMS_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
			</span>
		</span>

		<?php if(!empty($item->filesize)): ?>
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_ITEMS_FILESIZE') ?>:</span>
			<span class="ars-value">
				<?php echo ArsHelperHtml::sizeFormat($item->filesize) ?>
			</span>
		</span>
		<?php endif; ?>

		<?php if(!empty($item->md5)): ?>
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_ITEMS_MD5') ?>:</span>
			<span class="ars-value">
				<?php echo $item->md5 ?>
			</span>
		</span>
		<?php endif; ?>

		<?php if(!empty($item->sha1)): ?>
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_ITEMS_SHA1') ?>:</span>
			<span class="ars-value">
				<?php echo $item->sha1 ?>
			</span>
		</span>
		<?php endif; ?>

	</div>

	<div class="ars-item-description">
		<?php echo ArsHelperHtml::preProcessMessage($item->description); ?>
	</div>

	<div>
		<?php
			$itemTitle = JText::_('LBL_ITEM_DOWNLOAD');
			$link = ArsHelperChameleon::getReadOn($itemTitle, $download_url);
			$link = str_replace('<a ','<a rel="nofollow"', $link);
			echo $link;
		?>
	</div>
	<div class="clr"></div>
</div>