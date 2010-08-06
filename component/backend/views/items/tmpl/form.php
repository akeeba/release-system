<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$editor =& JFactory::getEditor();
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_RELEASE_BASIC'); ?></legend>

		<div class="editform-row">
			<label for="release_id"><?php echo JText::_('LBL_ITEMS_RELEASE'); ?></label>
			<?php echo ArsHelperSelect::releases($this->item->release_id, 'release_id') ?>
		</div>
		<div class="editform-row">
			<label for="title"><?php echo JText::_('LBL_ITEMS_TITLE'); ?></label>
			<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
		</div>
		<div class="editform-row">
			<label for="alias"><?php echo JText::_('ALIAS'); ?></label>
			<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>" >
		</div>
		<div class="editform-row">
			<label for="type"><?php echo JText::_('LBL_ITEMS_TYPE'); ?></label>
			<?php echo ArsHelperSelect::itemtypes($this->item->type, 'type', array('onchange'=>'onTypeChange();')) ?>
		</div>
		<div class="editform-row" id="row-file" <?php if($this->item->type != 'file'):?>style="display: none"<?php endif;?> >
			<label for="filename"><?php echo JText::_('LBL_ITEMS_FILE'); ?></label>
			<span id="filename-container">
			<?php echo ArsHelperSelect::getfiles($this->item->filename, $this->item->release_id, $this->item->id, 'filename', array('onchange'=>'onFileChange();')) ?>
			</span>
		</div>
		<div class="editform-row" id="row-url" <?php if($this->item->type != 'link'):?>style="display: none"<?php endif;?> >
			<label for="url"><?php echo JText::_('LBL_ITEMS_LINK'); ?></label>
			<input type="text" name="url" id="url" value="<?php echo $this->item->url ?>" onblur="onLinkBlur();">
		</div>
		<div class="editform-row">
			<label for="hits"><?php echo JText::_('HITS'); ?></label>
			<input type="text" name="hits" id="hits" value="<?php echo $this->item->hits ?>">
		</div>
		<div class="editform-row">
			<label for="published"><?php echo JText::_('PUBLISHED'); ?></label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div class="editform-row editform-row-noheight">
			<label for="access"><?php echo JText::_('ACCESS'); ?></label>
			<?php echo JHTML::_('list.accesslevel', $this->item); ?>
		</div>
		<div class="editform-row editform-row-noheight">
			<label for="groups"><?php echo JText::_('LBL_CATEGORIES_GROUPS'); ?></label>
			<?php echo ArsHelperSelect::ambragroups($this->item->groups, 'groups') ?>
		</div>
		<div style="clear:left"></div>

	</fieldset>

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_ITEMS_DESCRIPTION'); ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
</form>

<script type="text/javascript">
	function onTypeChange()
	{
		/**/
		(function($){
			var type = $('#type').val();
			if(type == 'file') {
				$('#row-file').css('display', 'block');
				$('#row-url').css('display', 'none');

				var itemID = '<?php echo $this->item->id ?>';
				var releaseID = $('#release_id').val();
				var selected = $('#filename').val();
				$.get(
					'index.php',
					{
						'option':		'com_ars',
						'view':			'ajax',
						'format':		'raw',
						'task':			'getfiles',
						'item_id':		itemID,
						'release_id':	releaseID,
						'selected':		selected
					},
					function(data, textStatus) {
						$('#filename-container').html(data);
					}
				)
			} else {
				$('#row-file').css('display', 'none');
				$('#row-url').css('display', 'block');
			}
		})(akeeba.jQuery);
		/**/
	}

	function onLinkBlur()
	{
		(function($){
			$('#alias').val( basename($('#url').val()) );
		})(akeeba.jQuery);
	}

	function onFileChange()
	{
		(function($){
			$('#alias').val( basename($('#filename').val()) );
		})(akeeba.jQuery);
	}
</script>