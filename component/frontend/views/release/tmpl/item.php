<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$Itemid = JRequest::getInt('Itemid',0);
$Itemid = empty($Itemid) ? "" : "&Itemid=$Itemid";
$download_url = JRoute::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid);

?>
<div class="ars-browse-items">
	<h3 class="ars-items-title">
		<a href="<?php echo $download_url ?>">
			<?php echo $this->escape($item->title) ?>
		</a>
	</h3>
	<div class="ars-item-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_ITEMS_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
			</span>
		</span>
	</div>

	<div class="ars-item-description">
		<?php echo ArsHelperHtml::preProcessMessage($item->description); ?>
	</div>

	<div>
		<a class="readon" href="<?php echo $download_url?>">
			<?php echo JText::_('LBL_ITEM_DOWNLOAD') ?>
		</a>
	</div>
	<div class="clr"></div>
</div>