<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.framework');

$this->loadHelper('filter');
?>

<div class="alert alert-info">
	<?php echo JText::sprintf('COM_ARS_DLIDLABELS_MASTERDLID', ArsHelperFilter::myDownloadID()); ?>
</div>

<div class="nav">
	<button class="btn btn-primary" onclick="Joomla.submitbutton('add'); return false;">
		<span class="icon-white icon-plus-sign"></span>
		<?php echo JText::_('JNew') ?>
	</button>
	<button class="btn btn-danger" onclick="Joomla.submitbutton('remove'); return false;">
		<span class="icon-white icon-minus-sign"></span>
		<?php echo JText::_('JACTION_DELETE') ?>
	</button>
	<button class="btn" onclick="Joomla.submitbutton('publish'); return false;">
		<span class="icon-eye-open"></span>
		<?php echo JText::_('JPublished') ?>
	</button>
	<button class="btn" onclick="Joomla.submitbutton('unpublish'); return false;">
		<span class="icon-eye-close"></span>
		<?php echo JText::_('JUnPublished') ?>
	</button>
</div>

<?php echo $this->getRenderedForm(); ?>