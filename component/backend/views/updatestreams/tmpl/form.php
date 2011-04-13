<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_UPDATESTREAMS_BASIC'); ?></legend>

		<div class="editform-row">
			<label for="name"><?php echo JText::_('LBL_UPDATES_NAME'); ?></label>
			<input type="text" name="name" id="name" value="<?php echo $this->item->name ?>">
		</div>
		<div class="editform-row">
			<label for="alias">
				<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
				<?php echo JText::_('JFIELD_ALIAS_LABEL'); ?>
				<?php else: ?>
				<?php echo JText::_('ALIAS'); ?>
				<?php endif; ?>			
			</label>
			<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>" >
		</div>
		<div class="editform-row">
			<label for="type"><?php echo JText::_('LBL_UPDATES_TYPE'); ?></label>
			<?php echo ArsHelperSelect::updatetypes($this->item->type, 'type') ?>
		</div>
		<div class="editform-row">
			<label for="category"><?php echo JText::_('LBL_RELEASES_CATEGORY'); ?></label>
			<?php echo ArsHelperSelect::categories($this->item->category, 'category') ?>
		</div>
		<div class="editform-row">
			<label for="packname"><?php echo JText::_('LBL_UPDATES_PACKNAME'); ?></label>
			<input type="text" name="packname" id="packname" value="<?php echo $this->item->packname ?>" >
		</div>
		<div class="editform-row">
			<label for="element"><?php echo JText::_('LBL_UPDATES_ELEMENT'); ?></label>
			<input type="text" name="element" id="element" value="<?php echo $this->item->element ?>" >
		</div>
		<div class="editform-row">
			<label for="published">
				<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
				<?php echo JText::_('JPUBLISHED'); ?>
				<?php else: ?>
				<?php echo JText::_('PUBLISHED'); ?>
				<?php endif; ?>
			</label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div style="clear:left"></div>
	</fieldset>
</form>