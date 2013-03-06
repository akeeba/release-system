<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('chameleon');
$this->loadHelper('html');

$Itemid = $this->input->getInt('Itemid', 0);
?>
<div class="ars-browse-category">
	<div class="ars-category-description">
		<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.category_description') ?>
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