<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" id="task" value="category" />
	<input type="hidden" name="folder" id="folder" value="<?php echo isset($this->folder) ? $this->escape($this->folder) : '' ?>" />
	<input type="hidden" name="file" id="file" value="" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<div id="category-selection" class="ui-widget ui-corner-all">
		<div class="ui-widget-header ui-corner-top">
			<label for="category">
				<?php echo JText::_('LBL_CATEGORY_SELECT');?>
			</label>
		</div>
		<div class="ui-widget-content ui-corner-bottom">
			<?php echo ArsHelperSelect::categories($this->category, 'id', array('onchange'=>'document.forms.adminForm.submit()')) ?>
			<input type="submit" value="<?php echo JText::_('GO') ?>" />
			<?php if(!empty($this->folder)): ?>
			<br/>
			<label for="subfoldername"><?php echo JText::_('LBL_SUBFOLDER_NAME') ?></label>
			<span id="subfoldername"><?php echo $this->escape($this->folder); ?></span>
			<?php endif; ?>
		</div>
	</div>
</form>