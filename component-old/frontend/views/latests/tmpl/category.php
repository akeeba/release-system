<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var ArsViewLatests $this */

JLoader::import('joomla.utilities.date');
$released = new JDate($item->release->created);
$release_url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->release->id.'&Itemid=' . $Itemid);

switch ($item->release->maturity)
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

<div class="ars-category-<?php echo $id ?> well">
	<h4 class="<?php echo $item->type == 'bleedingedge' ? 'warning' : '' ?>">
		<span class="label <?php echo $maturityClass ?> pull-right">
			<?php echo JText::_('COM_ARS_RELEASES_MATURITY_' . $item->release->maturity) ?>
		</span>
		<a href="<?php echo htmlentities($release_url) ?>">
			<?php echo $this->escape($item->title) ?> <?php echo $this->escape($item->release->version) ?>
		</a>
	</h4>

	<div class="ars-latest-category">
		<div class="ars-category-description">
			<?php echo ArsHelperHtml::preProcessMessage($item->description, 'com_ars.category_description') ?>
		</div>

		<dl class="dl-horizontal ars-release-properties">
			<dt>
				<?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>:
			</dt>
			<dd>
				<?php echo JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->release->maturity)) ?>
			</dd>

			<dt>
				<?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:
			</dt>
			<dd>
				<?php echo JHTML::_('date', $released, JText::_('DATE_FORMAT_LC2')) ?>
			</dd>
		</dl>

		<table class="table table-striped">
			<?php foreach($item->release->files as $i): ?>
			<?php echo $this->loadAnyTemplate('site:com_ars/latests/item', array('Itemid' => $Itemid, 'item' => $i)); ?>
			<?php endforeach; ?>
		</table>

		<p class="readmore">
			<a href="<?php echo htmlentities($release_url); ?>">
				<?php echo JText::_('LBL_RELEASE_VIEWITEMS') ?>
			</a>
		</p>

	</div>
</div>