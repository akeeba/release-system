<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.framework');

// Render the form
echo $this->getRenderedForm();
?>
<div class="form-actions">
	<button class="btn btn-primary" onclick="Joomla.submitbutton('save'); return false;">
		<?php echo JText::_('JSAVE') ?>
	</button>
	<button class="btn" onclick="Joomla.submitbutton('cancel'); return false;">
		<?php echo JText::_('JCANCEL') ?>
	</button>
</div>