<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$editor =& JFactory::getEditor();
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_RELEASE_BASIC'); ?></legend>

		<div class="editform-row">
			<label for="category_id"><?php echo JText::_('LBL_RELEASES_CATEGORY'); ?></label>
			<?php echo ArsHelperSelect::categories($this->item->category_id, 'category_id') ?>
		</div>
		<div class="editform-row">
			<label for="version"><?php echo JText::_('LBL_RELEASES_VERSION'); ?></label>
			<input type="text" name="version" id="version" value="<?php echo $this->item->version ?>">
		</div>
		<div class="editform-row">
			<label for="alias"><?php echo JText::_('LBL_RELEASES_ALIAS'); ?></label>
			<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>">
		</div>
		<div class="editform-row">
			<label for="maturity"><?php echo JText::_('LBL_RELEASES_MATURITY'); ?></label>
			<?php echo ArsHelperSelect::maturities($this->item->maturity, 'maturity') ?>
		</div>
		<div class="editform-row">
			<label for="hits"><?php echo JText::_('HITS'); ?></label>
			<input type="text" name="hits" id="hits" value="<?php echo $this->item->hits ?>">
		</div>
		<div class="editform-row">
			<label for="published"><?php echo JText::_('PUBLISHED'); ?></label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div class="editform-row editform-row-noheight">
			<label for="access"><?php echo JText::_('ACCESS'); ?></label>
			<?php echo JHTML::_('list.accesslevel', $this->item); ?>
		</div>
		<div class="editform-row editform-row-noheight">
			<label for="groups"><?php echo JText::_('LBL_CATEGORIES_GROUPS'); ?></label>
			<?php echo ArsHelperSelect::ambragroups($this->item->groups, 'groups') ?>
		</div>
		<div style="clear:left"></div>

	</fieldset>

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_RELEASE_DESCRIPTION'); ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_RELEASE_NOTES'); ?></legend>
		<?php echo $editor->display( 'notes',  $this->item->notes, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
</form>