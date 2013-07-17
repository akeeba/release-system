<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
<div class="ars-categories-<?php echo $renderSection ?>">

	<?php if (!empty($title)): ?>
	<div class="page-header">
		<h2><?php echo JText::_($title) ?></h2>
	</div>
	<?php endif; ?>

	<?php if (empty($this->items)): ?>
	<p class="muted ars-no-items">
		<?php echo JText::_('ARS_NO_CATEGORIES'); ?>
	</p>
	<?php else:?>
	<?php foreach($this->vgroups as $vgroupID => $vgroup): ?>
	<?php if ($vgroup->numitems[$renderSection] == 0) {
		continue;
	} ?>
	<div class="ars-vgroup-<?php $vgroupID ?>">
		<?php if($vgroup->title): ?>
		<h3 class="ars-vgroup-<?php $vgroupID ?>-title">
			<?php echo $vgroup->title; ?>
		</h3>
		<?php if ($vgroup->description): ?>
		<div class="ars-vgroup-<?php $vgroupID ?>-description">
			<?php echo $vgroup->description; ?>
		</div>
		<?php endif; ?>
		<?php endif; ?>

		<?php foreach($this->items[$renderSection] as $id => $item): ?>
		<div class="ars-category-<?php echo $id ?> well">
			<h4 class="<?php echo $item->type == 'bleedingedge' ? 'warning' : '' ?>">
				<a href="<?php echo htmlentities(AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid=' . $this->Itemid)) ?>">
					<?php echo $this->escape($item->title) ?>
				</a>
			</h4>

			<div class="ars-browse-category">
				<div class="ars-category-description">
					<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.category_description') ?>
				</div>
				<?php if($item->id): ?>
				<p class="readmore">
					<a href="<?php echo htmlentities(AKRouter::_('index.php?option=com_ars&view=category&id='.$item->id.'&Itemid='.$this->Itemid)); ?>">
						<?php echo JText::_('LBL_CATEGORY_VIEW') ?>
					</a>
				</p>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>

</div>