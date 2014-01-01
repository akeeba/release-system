<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
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
	<?php foreach($this->vgroups as $vgroup): ?>
	<?php if ($vgroup->numitems[$renderSection] == 0) {
		continue;
	} ?>
	<div class="ars-vgroup-<?php $vgroup->id ?>">
		<?php if($vgroup->title): ?>
		<h3 class="ars-vgroup-<?php $vgroup->id ?>-title">
			<?php echo $vgroup->title; ?>
		</h3>
		<?php if ($vgroup->description): ?>
		<div class="ars-vgroup-<?php $vgroup->id ?>-description">
			<?php echo $vgroup->description; ?>
		</div>
		<?php endif; ?>
		<?php endif; ?>

		<?php foreach($this->items[$renderSection] as $id => $item): ?>
		<?php if($item->vgroup_id != $vgroup->id) continue;?>
		<?php echo $this->loadAnyTemplate('site:com_ars/browses/category', array('id' => $id, 'item' => $item, 'Itemid' => $this->Itemid)); ?>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>

</div>