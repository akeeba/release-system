<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$Itemid_query = empty($Itemid) ? "" : "&Itemid=$Itemid";
$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid_query);

?>
<li class="ars-latest-items ars-row<?php echo $i ?>">
	<span class="ars-latest-items-title">
		<?php echo $this->escape($item->title) ?>
	</span>
	<span class="ars-latest-items-filename">
		<a href="<?php echo $download_url ?>" rel="nofollow">
		<?php echo $this->escape($item->alias) ?>
		</a>
	</span>
	<span class="ars-latest-items-downloaded">
		<?php echo JText::_('LBL_ITEMS_HITS') ?>
		<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
	</span>
</li>