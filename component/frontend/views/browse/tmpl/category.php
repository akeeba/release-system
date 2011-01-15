<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

?>
<div class="ars-browse-category">
	<h3 class="ars-category-title">
		<a href="<?php echo AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid='.$Itemid) ?>">
			<?php echo $item->title ?>
		</a>
	</h3>
	<div class="ars-category-description">
		<?php echo ArsHelperHtml::preProcessMessage($item->description) ?>
	</div>
	<div>
		<a class="readon" href="<?php echo AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid='.$Itemid) ?>">
			<?php echo JText::_('LBL_CATEGORY_VIEW') ?>
		</a>
	</div>
	<div class="clr"></div>
</div>