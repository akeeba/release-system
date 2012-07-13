<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$editor = JFactory::getEditor();

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_AUTODESC_BASIC'); ?></legend>

		<div class="editform-row">
			<label for="category"><?php echo JText::_('LBL_AUTODESC_CATEGORY'); ?></label>
			<?php echo ArsHelperSelect::categories($this->item->category, 'category') ?>
		</div>
		<div class="editform-row">
			<label for="packname"><?php echo JText::_('LBL_AUTODESC_PACKNAME'); ?></label>
			<input type="text" name="packname" id="packname" value="<?php echo $this->item->packname ?>">
		</div>
		<div class="editform-row">
			<label for="title"><?php echo JText::_('LBL_AUTODESC_TITLE'); ?></label>
			<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
		</div>
		<div class="editform-row">
			<label for="environments"><?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?></label>
			<span style="float: left"><?php echo ArsHelperSelect::environments($this->item->environments); ?></span>
		</div>
		<div style="clear:both"></div>
		<div class="editform-row">
			<label for="published">
				<?php echo JText::_('JPUBLISHED'); ?>
			</label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div style="clear:left"></div>
	</fieldset>

	<fieldset>
		<legend><?php echo JText::_('LBL_AUTODESC_DESCRIPTION'); ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
</form>