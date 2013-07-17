<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.utilities.date');

$released = new JDate($item->created);
$release_url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);

if(version_compare(JVERSION, '3.0', 'lt')) {
	JLoader::import('joomla.html.pane');
	$tabs	= JPane::getInstance('tabs');
}

switch ($item->maturity)
{
	case 'stable':
		$maturityClass = 'label-success';
		break;

	case 'rc':
		$maturityClass = 'label-info';
		break;

	case 'beta':
		$maturityClass = 'label-warning';
		break;

	case 'alpha':
		$maturityClass = 'label-important';
		break;

	default:
		$maturityClass = 'label-inverse';
		break;
}

?>

<div class="ars-release-<?php echo $item->id ?> well">
	<h4>
		<span class="label <?php echo $maturityClass ?> pull-right">
			<?php echo JText::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>
		</span>
		<a href="<?php echo htmlentities($release_url) ?>">
			<?php echo $this->escape($item->version) ?>
		</a>
	</h4>



	<?php if(!isset($no_link)): ?>

	<dl class="dl-horizontal ars-release-properties">
		<dt>
			<?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>:
		</dt>
		<dd>
			<?php echo JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity)) ?>
		</dd>

		<dt>
			<?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:
		</dt>
		<dd>
			<?php echo JHTML::_('date',$released, JText::_('DATE_FORMAT_LC2')) ?>
		</dd>

		<dt>
			<?php echo JText::_('LBL_RELEASES_HITS') ?>:
		</dt>
		<dd>
			<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
		</dd>

	</dl>

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

	<p class="readmore">
		<a href="<?php echo htmlentities($release_url); ?>">
			<?php echo JText::_('LBL_RELEASE_VIEWITEMS') ?>
		</a>
	</p>

	<?php else: ?>
	<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.release_description'); ?>
	<?php endif; ?>
</div>