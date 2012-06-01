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
	<input type="hidden" name="view" value="vgroups" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<div class="editform-row">
		<label for="title"><?php echo JText::_('LBL_VGROUPS_TITLE'); ?></label>
		<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
	</div>
	<div class="editform-row">
		<label for="published">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<div>
			<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
		</div>
	</div>
	<div style="clear:left"></div>
</form>