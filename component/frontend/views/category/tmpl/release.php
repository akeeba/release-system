<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$Itemid = JRequest::getInt('Itemid',0);

jimport('joomla.utilities.date');

$released = new JDate($item->created);
$release_url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);

jimport('joomla.html.pane');
$tabs	=& JPane::getInstance('tabs');

?>
<div class="ars-browse-releases">
	<?php if($item->id): ?>
	<div class="ars-release-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_MATURITY') ?>:</span>
			<span class="ars-value">
				<?php echo JText::_('LBL_RELEASES_MATURITY_'.  strtoupper($item->maturity)) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:</span>
			<span class="ars-value">
				<?php if(version_compare(JVERSION,'1.6.0','ge')):?>
				<?php echo JHTML::_('date',$released, JText::_('DATE_FORMAT_LC2')) ?>
				<?php else: ?>
				<?php echo $released->toFormat(JText::_('DATE_FORMAT_LC2')) ?>
				<?php endif; ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
			</span>
		</span>
	</div>

	<?php echo $tabs->startPane('reltabs-'.$item->id); ?>
		<?php echo $tabs->startPanel(JText::_('LBL_ARS_RELEASE_DESCRIPTION'),'reltabs-'.$item->id.'-desc') ?>
			<?php echo ArsHelperHtml::preProcessMessage($item->description); ?>
		<?php echo $tabs->endPanel(); ?>
		<?php echo $tabs->startPanel(JText::_('LBL_ARS_RELEASE_NOTES'),'reltabs-'.$item->id.'-notes') ?>
			<?php echo ArsHelperHtml::preProcessMessage($item->notes) ?>
		<?php echo $tabs->endPanel(); ?>
	<?php echo $tabs->endPane(); ?>

	<div class="ars-release-readon">
		<?php
			$url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);
			$title = JText::_('LBL_RELEASE_VIEWITEMS');
			echo ArsHelperChameleon::getReadOn($title, $url);
		?>
	</div>
	<?php else: ?>
	<?php echo ArsHelperHtml::preProcessMessage($item->description); ?>
	<?php endif; ?>
</div>