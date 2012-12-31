<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$Itemid = FOFInput::getInt('Itemid', 0, $this->input);

jimport('joomla.utilities.date');

$released = new JDate($item->created);
$release_url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);

if(version_compare(JVERSION, '3.0', 'lt')) {
	jimport('joomla.html.pane');
	$tabs	= JPane::getInstance('tabs');
}

?>
<div class="ars-browse-releases">
	<?php if($item->id): ?>
	<div class="ars-release-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>:</span>
			<span class="ars-value">
				<?php echo JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity)) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:</span>
			<span class="ars-value">
				<?php echo JHTML::_('date',$released, JText::_('DATE_FORMAT_LC2')) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
			</span>
		</span>
	</div>

	<?php if(version_compare(JVERSION, '3.0', 'lt')): ?>
	<?php echo $tabs->startPane('reltabs-'.$item->id); ?>
		<?php echo $tabs->startPanel(JText::_('COM_ARS_RELEASE_DESCRIPTION_LABEL'),'reltabs-'.$item->id.'-desc') ?>
			<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.release_description'); ?>
		<?php echo $tabs->endPanel(); ?>
		<?php echo $tabs->startPanel(JText::_('COM_ARS_RELEASE_NOTES_LABEL'),'reltabs-'.$item->id.'-notes') ?>
			<?php echo ArsHelperHtml::preProcessMessage($item->notes, 'com_ars.release_notes') ?>
		<?php echo $tabs->endPanel(); ?>
	<?php echo $tabs->endPane(); ?>
	<?php else: ?>
	<ul class="nav nav-tabs">
		<li class="active"><a href="#reltabs-<?php echo $item->id ?>-desc" data-toggle="tab"><?php echo JText::_('COM_ARS_RELEASE_DESCRIPTION_LABEL') ?></a>
		<li><a href="#reltabs-<?php echo $item->id ?>-notes" data-toggle="tab"><?php echo JText::_('COM_ARS_RELEASE_NOTES_LABEL') ?></a>
	</ul>
	<div class="tab-content">
		<div class="tab-pane active" id="reltabs-<?php echo $item->id ?>-desc">
			<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.release_description'); ?>
		</div>
		<div class="tab-pane" id="reltabs-<?php echo $item->id ?>-notes">
			 <?php echo ArsHelperHtml::preProcessMessage($item->notes, 'com_ars.release_notes'); ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="ars-release-readon">
		<?php
			$url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);
			$title = JText::_('LBL_RELEASE_VIEWITEMS');
			echo ArsHelperChameleon::getReadOn($title, $url);
		?>
	</div>
	<?php else: ?>
	<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.release_description'); ?>
	<?php endif; ?>
</div>