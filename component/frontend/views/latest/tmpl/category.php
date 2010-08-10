<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$released = new JDate($cat->release->created);
?>
<div class="ars-list-release">
	<h3 class="ars-release-title">
		<img src="<?php echo JURI::base(); ?>/media/com_ars/icons/status_<?php echo $cat->release->maturity ?>.png" width="16" height="16" align="left" />
		&nbsp;
		<a href="<?php echo JRoute::_('index.php?option=com_ars&view=release&id='.$cat->release->id.'&Itemid='.$Itemid) ?>">
			<?php echo $cat->title ?> <?php echo $cat->release->version?>
		</a>
	</h3>
	<div class="ars-release-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_MATURITY') ?>:</span>
			<span class="ars-value">
				<?php echo JText::_('LBL_RELEASES_MATURITY_'.  strtoupper($cat->release->maturity)) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:</span>
			<span class="ars-value">
				<?php echo $released->toFormat(JText::_('DATE_FORMAT_LC2')) ?>
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
				include dirname(__FILE__).DS.'item.php';
			}
		?>
		</ul>
	</div>
	<div>
		<a class="readon" href="<?php echo JRoute::_('index.php?option=com_ars&view=category&id='.$cat->id.'&Itemid='.$Itemid) ?>">
			<?php echo JText::_('LBL_CATEGORY_VIEW') ?>
		</a>
	</div>
	<div class="clr"></div>
</div>