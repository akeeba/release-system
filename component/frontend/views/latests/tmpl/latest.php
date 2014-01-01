<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
<div class="item-page<?php echo $this->cparams->get('pageclass_sfx') ?>">

<?php if ($this->cparams->get('show_page_heading', 1)): ?>
	<div class="page-header">
		<h1>
			<?php echo $this->escape($this->cparams->get('page_heading')); ?>
		</h1>
	</div>
<?php elseif (!$this->cparams->get('show_page_heading', 1)): ?>
	<div class="page-header">
		<h1>
			<?php echo JText::_('ARS_VIEW_LATEST_TITLE'); ?>
		</h1>
	</div>
<?php endif; ?>

<?php if( array_key_exists('all', $this->items) ): ?>
	<?php echo $this->loadAnyTemplate('site:com_ars/latest/generic', array('renderSection' => 'all', 'title' => '')); ?>
<?php else: ?>
	<?php echo $this->loadAnyTemplate('site:com_ars/latest/generic', array('renderSection' => 'normal', 'title' => 'ARS_CATEGORY_NORMAL')); ?>
	<?php echo $this->loadAnyTemplate('site:com_ars/latest/generic', array('renderSection' => 'bleedingedge', 'title' => 'ARS_CATEGORY_BLEEDINGEDGE')); ?>
<?php endif; ?>

</div>