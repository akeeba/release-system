<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('filtering');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

?>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="com_ars" />
	<input type="hidden" name="view" value="categories" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('COM_ARS_CATEGORY_BASIC_LABEL'); ?></h3>

		<div class="control-group">
			<label for="title" class="control-label"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_TITLE'); ?></label>
			<div class="controls">
				<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="alias" class="control-label"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_ALIAS'); ?></label>
			<div class="controls">
				<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="vgroup_id" class="control-label"><?php echo JText::_('LBL_CATEGORIES_VGROUP'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::vgroups($this->item->vgroup_id, 'vgroup_id') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="type" class="control-label"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_TYPE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::categorytypes($this->item->type, 'type') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="directory" class="control-label"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_DIRECTORY'); ?></label>
			<div class="controls">
				<input type="text" name="directory" id="directory" value="<?php echo $this->item->directory ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="published" class="control-label"><?php echo JText::_('JPUBLISHED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div class="control-group">
			<label for="access" class="control-label"><?php echo JText::_('JFIELD_ACCESS_LABEL'); ?></label>
			<div class="controls">
				<?php if(version_compare(JVERSION, '3.0', 'gt')): ?>
				<?php
					$options = array(JHtml::_('select.option', '', JText::_('COM_ARS_COMMON_SHOW_ALL_LEVELS')));
					echo JHTML::_('access.level', 'access', $this->item->access, '', $options);
				?>
				<?php else: ?>
				<?php echo JHTML::_('list.accesslevel', $this->item); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if(ArsHelperFiltering::hasAkeebaSubs()): ?>
		<div class="control-group">
			<label for="groups" class="control-label"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_AKEEBA_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::akeebasubsgroups($this->item->groups, 'groups') ?>
			</div>
		</div>
		<?php elseif(defined('PAYPLANS_LOADED')): ?>
		<div class="control-group">
			<label for="groups" class="control-label"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_PAYPLANS_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::payplansgroups($this->item->groups, 'groups') ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="control-group">
			<label for="language" class="control-label"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::languages($this->item->language, 'language') ?>
			</div>
		</div>
	</div>

	<div class="span6">
		<h3><?php echo JText::_('COM_ARS_CATEGORY_DESCRIPTION_LABEL'); ?></h3>

		<?php echo $editor->display( 'description',  $this->item->description, '97%', '350', '60', '20', array() ) ; ?>
	</div>
</form>