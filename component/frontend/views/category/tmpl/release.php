<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$Itemid = JRequest::getInt('Itemid',0);

jimport('joomla.utilities.date');

$released = new JDate($item->created);
$release_url = JRoute::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);

?>
<div class="ars-browse-releases">
	<h3 class="ars-release-title">
		<img src="<?php echo JURI::base(); ?>/media/com_ars/icons/status_<?php echo $item->maturity ?>.png" width="16" height="16" align="left" />
		&nbsp;
		<a href="<?php echo $release_url ?>">
			<span class="ars-release-title-version"><?php echo $this->escape($item->version) ?></span>
			<span class="ars-release-title-maturity">(<?php echo JText::_('LBL_RELEASES_MATURITY_'.  strtoupper($item->maturity)) ?>)</span>
		</a>
	</h3>
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
				<?php echo $released->toFormat(JText::_('DATE_FORMAT_LC2')) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $item->hits) ?>
			</span>
		</span>
	</div>

	<div id="reltabs-<?php echo $item->id ?>">
		<ul>
			<li>
				<a href="#reltabs-<?php echo $item->id ?>-desc">
				<?php echo JText::_('LBL_ARS_RELEASE_DESCRIPTION') ?>
				</a>
			</li>
			<li>
				<a href="#reltabs-<?php echo $item->id ?>-notes">
				<?php echo JText::_('LBL_ARS_RELEASE_NOTES') ?>
				</a>
			</li>
		</ul>
		<div id="reltabs-<?php echo $item->id ?>-desc" class="ars-release-description">
			<?php echo ArsHelperHtml::preProcessMessage($item->description); ?>
		</div>
		<div id="reltabs-<?php echo $item->id ?>-notes" class="ars-release-notes">
			<?php echo ArsHelperHtml::preProcessMessage($item->notes) ?>
		</div>
	</div>
	<?php $tabs[] = "reltabs-{$item->id}"; ?>

	<div>
		<a class="readon" href="<?php echo $release_url?>">
			<?php echo JText::_('LBL_RELEASE_VIEWITEMS') ?>
		</a>
	</div>
	<div class="clr"></div>
</div>