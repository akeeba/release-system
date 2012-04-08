<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$editor = JFactory::getEditor();
$filteringModel = JModel::getInstance('Filtering','ArsModel');
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
</form>