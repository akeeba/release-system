<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$editor = JFactory::getEditor();
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_CATEGORY_BASIC'); ?></legend>
		
		<div class="editform-row">
			<label for="title"><?php echo JText::_('LBL_ENVIRONMENT_TITLE'); ?></label>
			<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
		</div>
		<div class="editform-row">
			<label for="xmltitle"><?php echo JText::_('LBL_ENVIRONMENT_XMLTITLE'); ?></label>
			<input type="text" name="xmltitle" id="xmltitle" value="<?php echo $this->item->xmltitle ?>" title="<?php echo JText::_('LBL_ENVIRONMENT_XMLTITLE_TIP'); ?>">
		</div>
		<div class="editform-row">
			<label for="icon"><?php echo JText::_('LBL_ENVIRONMENT_ICON'); ?></label>
			<?php echo ArsHelperSelect::environmenticons( $this->item->icon ) ?>
		</div>
	</fieldset>
</form>