<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('chameleon');
$this->loadHelper('router');

$Itemid = FOFInput::getInt('Itemid', 0, $this->input);
?>
<?php if($this->cparams->get('show_page_heading', 1)): ?>
	<h2 class="componentheading<?php echo $this->escape($this->cparams->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->cparams->get('page_heading')); ?></h2>
<?php elseif(!$this->cparams->get('show_page_heading', 1)): ?>
	<h2 class="componentheading<?php echo $this->escape($this->cparams->get('pageclass_sfx')); ?>"><?php echo JText::_('ARS_VIEW_LATEST_TITLE'); ?></h2>
<?php endif; ?>

<?php if( array_key_exists('all', $this->items) ): ?>
<div id="ars-categories-all">
	<?php if(!empty($this->items)): ?>
	<?php foreach($this->vgroups as $vgroupID => $vgroupTitle): ?>
	<?php $echoedVgroupTitle = false; ?>
	<?php
		foreach($this->items['all'] as $id => $cat):
			if($cat->vgroup_id != $vgroupID) continue;
			if( !empty($cat->release) )
				if( !empty($cat->release->files) )
				{
					$params = ArsHelperChameleon::getParams('category');
					@ob_start();
					echo $this->loadAnyTemplate('site:com_ars/latests/category', array('Itemid' => $Itemid, 'cat' => $cat, 'id' => $id));
					$contents = ob_get_clean();
					$module = ArsHelperChameleon::getModule($cat->title, $contents, $params);
					echo JModuleHelper::renderModule($module, $params);					
				}
		endforeach;
	?>
	<?php endforeach; ?>
	<?php else: ?>
	<div class="ars-nocategories">
		<?php echo JText::_('ARS_NO_CATEGORIES'); ?>
	</div>
	<?php endif; ?>
</div>
<?php else: ?>

<div id="ars-categories-normal">
	<h2><?php echo JText::_('ARS_CATEGORY_NORMAL'); ?></h2>

	<?php if(!empty($this->items['normal'])): ?>
	<?php foreach($this->vgroups as $vgroupID => $vgroupTitle): ?>
	<?php $echoedVgroupTitle = false; ?>
	<?php
		foreach($this->items['normal'] as $id => $cat):
			if($cat->vgroup_id != $vgroupID) continue;
			//echo "<pre>";var_dump($cat);echo "</pre><hr/>";
			if( !empty($cat->release) )
				if( !empty($cat->release->files) )
				{
					$params = ArsHelperChameleon::getParams('category');
					if(!$echoedVgroupTitle) {
						$echoedVgroupTitle = true;
						if($vgroupTitle):?>
		<h3><?php echo $vgroupTitle; ?></h3>
						<?php endif;
					}
					@ob_start();
					echo $this->loadAnyTemplate('site:com_ars/latests/category', array('Itemid' => $Itemid, 'cat' => $cat, 'id' => $id));
					$contents = ob_get_clean();
					$module = ArsHelperChameleon::getModule($cat->title, $contents, $params);
					echo JModuleHelper::renderModule($module, $params);					
				}
		endforeach;
	?>
	<?php endforeach; ?>
	<?php else: ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_CATEGORIES'); ?>
	</div>
	<?php endif; ?>
</div>

<div id="ars-categories-bleedingedge">
	<h2><?php echo JText::_('ARS_CATEGORY_BLEEDINGEDGE'); ?></h2>

	<?php if(!empty($this->items['bleedingedge'])): ?>
	<?php foreach($this->vgroups as $vgroupID => $vgroupTitle): ?>
	<?php $echoedVgroupTitle = false; ?>
	<?php
		foreach($this->items['bleedingedge'] as $id => $cat):
			if($cat->vgroup_id != $vgroupID) continue;
			if( !empty($cat->release) )
				if( !empty($cat->release->files) )
				{
					$params = ArsHelperChameleon::getParams('category', true);
					if(!$echoedVgroupTitle) {
						$echoedVgroupTitle = true;
						if($vgroupTitle):?>
		<h3><?php echo $vgroupTitle; ?></h3>
						<?php endif;
					}
					@ob_start();
					echo $this->loadAnyTemplate('site:com_ars/latests/category', array('Itemid' => $Itemid, 'cat' => $cat, 'id' => $id));
					$contents = ob_get_clean();
					$module = ArsHelperChameleon::getModule($cat->title, $contents, $params);
					echo JModuleHelper::renderModule($module, $params);					
				}
		endforeach;
	?>
	<?php endforeach; ?>
	<?php else: ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_CATEGORIES'); ?>
	</div>
	<?php endif; ?>
</div>

<?php endif; ?>