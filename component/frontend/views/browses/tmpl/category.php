<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$category_url = AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid=' . $Itemid);

?>
<div class="ars-category-<?php echo $id ?> well">
	<h4 class="<?php echo $item->type == 'bleedingedge' ? 'warning' : '' ?>">
		<a href="<?php echo htmlentities($category_url) ?>">
			<?php echo $this->escape($item->title) ?>
		</a>
	</h4>

	<div class="ars-browse-category">
		<div class="ars-category-description">
			<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.category_description') ?>
		</div>
		<?php if(!isset($no_link)): ?>
		<p class="readmore">
			<a href="<?php echo htmlentities($category_url); ?>" class="btn btn-primary">
				<?php echo JText::_('LBL_CATEGORY_VIEW') ?>
			</a>
		</p>
		<?php endif; ?>
	</div>
</div>