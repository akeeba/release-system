<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

jimport('joomla.utilities.date');
$released = new JDate($cat->release->created);
?>
<div class="ars-list-release">
	<div class="ars-release-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>:</span>
			<span class="ars-value">
				<?php echo JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($cat->release->maturity)) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:</span>
			<span class="ars-value">
				<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? $released->format(JText::_('DATE_FORMAT_LC2'), true) : $released->toFormat(JText::_('DATE_FORMAT_LC2')) ?>
			</span>
		</span>
	</div>

	<div class="ars-releases-latest">
		<ul>
		<?php
			$i = 0;
			foreach($cat->release->files as $item)
			{
				$i = 1 - $i;
				echo $this->loadAnyTemplate('site:com_ars/latests/item', array('Itemid' => $Itemid, 'item' => $item));
			}
		?>
		</ul>
	</div>
	<div class="ars-category-readon">
		<?php 
		$title = JText::_('LBL_CATEGORY_VIEW');
		$url = AKRouter::_('index.php?option=com_ars&view=category&id='.$cat->id.'&Itemid='.$Itemid);
		echo ArsHelperChameleon::getReadOn($title, $url);
		?>
	</div>
	<div class="clr"></div>
</div>