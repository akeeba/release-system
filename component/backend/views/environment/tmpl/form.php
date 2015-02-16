<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var F0FViewHtml $this */

$editor = JFactory::getEditor();

JHtml::_('formbehavior.chosen', 'select');

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
		<h3><?php echo JText::_('COM_ARS_CATEGORY_BASIC_LABEL'); ?></h3>

		<div class="control-group">
			<label for="title" class="control-label"><?php echo JText::_('LBL_ENVIRONMENT_TITLE'); ?></label>
			<div class="controls">
				<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="xmltitle" class="control-label"><?php echo JText::_('LBL_ENVIRONMENT_XMLTITLE'); ?></label>
			<div class="controls">
				<input type="text" name="xmltitle" id="xmltitle" value="<?php echo $this->item->xmltitle ?>" title="<?php echo JText::_('LBL_ENVIRONMENT_XMLTITLE_TIP'); ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="icon" class="control-label"><?php echo JText::_('LBL_ENVIRONMENT_ICON'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::environmenticons( $this->item->icon ) ?>
			</div>
		</div>
	</div>

</div>
</form>