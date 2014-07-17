<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var F0FViewHtml $this */

$this->loadHelper('select');

F0FTemplateUtils::addCSS('media://com_ars/css/backend.css');
?>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo $this->input->getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo $this->input->getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">

	<div class="span12">
		<h3><?php echo JText::_('LBL_ARS_UPDATESTREAMS_BASIC'); ?></h3>

		<div class="control-group">
			<label for="name" class="control-label"><?php echo JText::_('LBL_UPDATES_NAME'); ?></label>
			<div class="controls">
				<input type="text" name="name" id="name" value="<?php echo $this->item->name ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="alias" class="control-label"><?php echo JText::_('JFIELD_ALIAS_LABEL'); ?></label>
			<div class="controls">
				<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="type" class="control-label"><?php echo JText::_('LBL_UPDATES_TYPE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::updatetypes($this->item->type, 'type') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="category" class="control-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::categories($this->item->category, 'category') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="packname" class="control-label"><?php echo JText::_('LBL_UPDATES_PACKNAME'); ?></label>
			<div class="controls">
				<input type="text" name="packname" id="packname" value="<?php echo $this->item->packname ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="element" class="control-label"><?php echo JText::_('LBL_UPDATES_ELEMENT'); ?></label>
			<div class="controls">
				<input type="text" name="element" id="element" value="<?php echo $this->item->element ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="client_id" class="control-label"><?php echo JText::_('LBL_RELEASES_CLIENT_ID'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::clientid($this->item->client_id, 'client_id') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="folder" class="control-label"><?php echo JText::_('LBL_UPDATES_FOLDER'); ?></label>
			<div class="controls">
				<input type="text" name="folder" id="folder" value="<?php echo $this->item->folder ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="jedid" class="control-label"><?php echo JText::_('LBL_UPDATES_JEDID'); ?></label>
			<div class="controls">
				<input type="text" name="jedid" id="jedid" value="<?php echo $this->item->jedid ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="published" class="control-label"><?php echo JText::_('JPUBLISHED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
	</div>
</div>
</form>
