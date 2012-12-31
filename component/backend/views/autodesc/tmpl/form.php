<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$editor = JFactory::getEditor();

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

?>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">
	
	<div class="span6">
		<h3><?php echo JText::_('LBL_ARS_AUTODESC_BASIC'); ?></h3>

		<div class="control-group">
			<label for="category" class="control-label"><?php echo JText::_('LBL_AUTODESC_CATEGORY'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::categories($this->item->category, 'category') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="packname" class="control-label"><?php echo JText::_('LBL_AUTODESC_PACKNAME'); ?></label>
			<div class="controls">
				<input type="text" name="packname" id="packname" value="<?php echo $this->item->packname ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="title" class="control-label"><?php echo JText::_('LBL_AUTODESC_TITLE'); ?></label>
			<div class="controls">
				<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="environments" class="control-label"><?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::environments($this->item->environments); ?>
			</div>
		</div>
		<div style="clear:both"></div>
		<div class="control-group">
			<label for="published" class="control-label"><?php echo JText::_('JPUBLISHED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
	</div>

	<div class="span6">
		<h3><?php echo JText::_('LBL_AUTODESC_DESCRIPTION'); ?></h3>
		<?php echo $editor->display( 'description',  $this->item->description, '97%', '350', '60', '20', array() ) ; ?>
	</div>
	
</div>
</form>