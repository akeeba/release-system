<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */
?>

	<div class="alert alert-info">
		<button class="close" data-dismiss="alert">×</button>
		<?php echo JText::_('LBL_UPDATESTREAMS_ALLLINKS_INTRO') ?>
		<a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=all&format=xml" target="_blank">
			<?php echo JText::_('LBL_UPDATESTREAMS_ALLLINKS') ?>
		</a>
	</div>


<?php
echo $this->getRenderedForm();
