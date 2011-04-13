<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

?>
<div class="ars-browse-category">
	<div class="ars-category-description">
		<?php echo ArsHelperHtml::preProcessMessage($item->description) ?>
	</div>
	<?php if($item->id): ?>
	<div>
		<?php
			$url = AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid='.$Itemid);
			$categoryTitle = JText::_('LBL_CATEGORY_VIEW');
			echo ArsHelperChameleon::getReadOn($categoryTitle, $url);
		?>
	</div>
	<?php endif; ?>
	<div class="clr"></div>
</div>