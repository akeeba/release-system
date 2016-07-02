<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\ReleaseSystem\Admin\View\Upload\Html $this */

JHtml::_('behavior.core');
JHtml::_('formbehavior.chosen', 'select');

$this->addCssFile('media://com_ars/css/backend.css');
?>
<div class="well well-small">
	<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
		<input type="hidden" name="option" value="com_ars"/>
		<input type="hidden" name="view" value="Upload"/>
		<input type="hidden" name="task" id="task" value="category"/>
		<input type="hidden" name="folder" id="folder"
		       value="<?php echo isset($this->folder) ? $this->escape($this->folder) : '' ?>"/>
		<input type="hidden" name="file" id="file" value=""/>
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1"/>

		<h3><?php echo JText::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL'); ?></h3>

		<?php echo Akeeba\ReleaseSystem\Admin\Helper\Select::categories($this->category, 'id', array(
			'onchange' => 'document.forms.adminForm.submit()',
			'class'    => 'input-medium'
		)) ?>
		<input type="submit" class="btn btn-mini" value="<?php echo JText::_('JSEARCH_FILTER_SUBMIT') ?>"/>
		<?php if (!empty($this->folder)): ?>
			<div class="clr"></div>
			<br/>
			<?php echo JText::_('LBL_SUBFOLDER_NAME') ?>
			<span id="subfoldername"><?php echo $this->escape($this->folder); ?></span>
		<?php endif; ?>

	</form>
</div>

<div class="ak_clr"></div>