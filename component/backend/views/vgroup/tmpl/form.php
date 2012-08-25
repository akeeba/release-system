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

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="vgroups" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />
	
<div class="row-fluid">
	
	<div class="span12">
		
	<div class="control-group">
		<label for="title" class="control-label"><?php echo JText::_('LBL_VGROUPS_TITLE'); ?></label>
		<div class="controls">
			<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
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