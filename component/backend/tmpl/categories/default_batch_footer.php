<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\ARS\Administrator\View\Categories\HtmlView $this */

$js = <<< JS
window.addEventListener('DOMContentLoaded', function() {
   document.getElementById('ars_batch_cancel').addEventListener('click', function() {
		document.getElementById('batch-language-id').value = '';
		document.getElementById('batch-access').value = '';
   }); 
   document.getElementById('ars_batch_run').addEventListener('click', function(e) {
       e.preventDefault();
       Joomla.submitbutton('category.batch');
   }); 
});
JS;

$this->document->getWebAssetManager()->addInlineScript($js, [], [], ['core']);

?>
<button class="btn btn-secondary" id="ars_batch_cancel" type="button"
		data-bs-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button class="btn btn-success" id="ars_batch_run" type="submit">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
