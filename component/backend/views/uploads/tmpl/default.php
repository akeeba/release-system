<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var ArsViewUploads $this */

$this->loadHelper('select');

F0FTemplateUtils::addCSS('media://com_ars/css/backend.css');
?>

<div class="row-fluid">
<div class="span12">

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo $this->input->getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo $this->input->getCmd('view') ?>" />
	<input type="hidden" name="task" id="task" value="category" />
	<input type="hidden" name="folder" id="folder" value="<?php echo isset($this->folder) ? $this->escape($this->folder) : '' ?>" />
	<input type="hidden" name="file" id="file" value="" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

		<h3><?php echo JText::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL');?></h3>

		<?php echo ArsHelperSelect::categories($this->category, 'id', array('onchange'=>'document.forms.adminForm.submit()','class' => 'input-medium')) ?>
		<input type="submit" class="btn btn-mini" value="<?php echo JText::_('JSEARCH_FILTER_SUBMIT') ?>" />
		<?php if(!empty($this->folder)): ?>
		<div class="clr"></div>
		<br/>
		<?php echo JText::_('LBL_SUBFOLDER_NAME') ?>
		<span id="subfoldername"><?php echo $this->escape($this->folder); ?></span>
		<?php endif; ?>

</form>

</div>
</div>