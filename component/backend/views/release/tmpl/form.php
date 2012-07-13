<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHTML::_('behavior.calendar');

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('filtering');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

if($this->item->id == 0) {
	$this->item->category_id = $this->getModel()->getState('category', 0);
}

?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

	<fieldset>
		<legend><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL'); ?></legend>

		<div class="editform-row">
			<label for="category_id"><?php echo JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'); ?></label>
			<?php echo ArsHelperSelect::categories($this->item->category_id, 'category_id') ?>
		</div>
		<div class="editform-row">
			<label for="version"><?php echo JText::_('COM_ARS_RELEASES_FIELD_VERSION'); ?></label>
			<input type="text" name="version" id="version" value="<?php echo $this->item->version ?>">
		</div>
		<div class="editform-row">
			<label for="alias">
				<?php echo JText::_('JFIELD_ALIAS_LABEL'); ?>
			</label>
			<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>">
		</div>
		<div class="editform-row">
			<label for="maturity"><?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY'); ?></label>
			<?php echo ArsHelperSelect::maturities($this->item->maturity, 'maturity') ?>
		</div>
		<div class="editform-row">
			<label for="hits">
				<?php echo JText::_('JGLOBAL_HITS'); ?>
			</label>
			<input type="text" name="hits" id="hits" value="<?php echo $this->item->hits ?>">
		</div>
		<div class="editform-row">
			<label for="published">
				<?php echo JText::_('JPUBLISHED'); ?>
			</label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div class="editform-row editform-row-noheight">
			<label for="access">
				<?php echo JText::_('JFIELD_ACCESS_LABEL'); ?>
			</label>
			<?php echo JHTML::_('list.accesslevel', $this->item); ?>
		</div>
		<?php if(ArsHelperFiltering::hasAkeebaSubs()): ?>
		<div class="editform-row editform-row-noheight">
			<label for="groups"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_AKEEBA_LABEL'); ?></label>
			<?php echo ArsHelperSelect::akeebasubsgroups($this->item->groups, 'groups') ?>
		</div>
		<?php endif; ?>
		<div style="clear:left"></div>
		<div class="editform-row">
			<label for="created"><?php echo JText::_('COM_ARS_RELEASES_FIELD_RELEASED'); ?></label>
			<div>
				<?php echo JHTML::_('calendar', $this->item->created, 'created', 'created'); ?>
			</div>
		</div>
		
		<div class="editform-row editform-row-noheight">
			<label for="language"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>
			<?php echo ArsHelperSelect::languages($this->item->language, 'language') ?>
		</div>
		<div style="clear:left"></div>

	</fieldset>

	<fieldset>
		<legend><?php echo JText::_('COM_ARS_RELEASE_DESCRIPTION_LABEL'); ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
	<fieldset>
		<legend><?php echo JText::_('COM_ARS_RELEASE_NOTES_LABEL'); ?></legend>
		<?php echo $editor->display( 'notes',  $this->item->notes, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
</form>