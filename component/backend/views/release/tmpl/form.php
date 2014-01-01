<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHTML::_('behavior.calendar');

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('filtering');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

if($this->item->id == 0) {
	$this->item->category_id = $this->getModel()->getState('category', 0);
}

?>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL'); ?></h3>

		<div class="control-group">
			<label for="category_id" class="control-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::categories($this->item->category_id, 'category_id') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="version" class="control-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_VERSION'); ?></label>
			<div class="controls">
				<input type="text" name="version" id="version" value="<?php echo $this->item->version ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="alias" class="control-label"><?php echo JText::_('JFIELD_ALIAS_LABEL'); ?></label>
			<div class="controls">
				<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="maturity" class="control-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::maturities($this->item->maturity, 'maturity') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="hits" class="control-label"><?php echo JText::_('JGLOBAL_HITS'); ?></label>
			<div class="controls">
				<input type="text" name="hits" id="hits" value="<?php echo $this->item->hits ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="published" class="control-label"><?php echo JText::_('JPUBLISHED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
	</div>

	<div class="span6">

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
		<div class="control-group">
			<label for="show_unauth_links" class="control-label"><?php echo JText::_('COM_ARS_COMMON_SHOW_UNAUTH_LINKS'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'show_unauth_links', null, $this->item->show_unauth_links); ?>
			</div>
		</div>
		<div class="control-group">
			<label for="redirect_unauth" class="control-label"><?php echo JText::_('COM_ARS_COMMON_REDIRECT_UNAUTH'); ?></label>
			<div class="controls">
				<input type="text" name="redirect_unauth" id="redirect_unauth" value="<?php echo $this->item->redirect_unauth ?>">
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
		<div style="clear:left"></div>
		<div class="control-group">
			<label for="created" class="control-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_RELEASED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('calendar', $this->item->created, 'created', 'created'); ?>
			</div>
		</div>

		<div class="control-group">
			<label for="language" class="control-label"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::languages($this->item->language, 'language') ?>
			</div>
		</div>

	</div>

</div>
<hr/>
<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('COM_ARS_RELEASE_DESCRIPTION_LABEL'); ?></h3>
		<?php echo $editor->display( 'description',  $this->item->description, '97%', '350', '60', '20', array() ) ; ?>
	</div>

	<div class="span6">
		<h3><?php echo JText::_('COM_ARS_RELEASE_NOTES_LABEL'); ?></h3>
		<?php echo $editor->display( 'notes',  $this->item->notes, '97%', '350', '60', '20', array() ) ; ?>
	</div>

</div>
</form>