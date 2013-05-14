<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('filtering');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

if($this->item->id == 0) {
	$this->item->release_id = $this->getModel()->getState('release', 0);
}

?>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">
	<div class="span12">
		<h3><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL'); ?></h3>
	</div>
</div>

<div class="row-fluid">

	<div class="span6">

		<div class="control-group">
			<label for="release_id" class="control-label"><?php echo JText::_('LBL_ITEMS_RELEASE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::releases($this->item->release_id, 'release_id') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="title" class="control-label"><?php echo JText::_('LBL_ITEMS_TITLE'); ?></label>
			<div class="controls">
				<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
			</div>
		</div>
		<div class="control-group">
			<label for="alias" class="control-label"><?php echo JText::_('JFIELD_ALIAS_LABEL'); ?></label>
			<div class="controls">
				<input type="text" name="alias" id="alias" value="<?php echo $this->item->alias ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="type" class="control-label"><?php echo JText::_('LBL_ITEMS_TYPE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::itemtypes($this->item->type, 'type', array('onchange'=>'onTypeChange();')) ?>
			</div>
		</div>
		<div class="control-group" id="row-file" <?php if($this->item->type != 'file'): ?>style="display:none"<?php endif; ?>>
			<label for="filename" class="control-label"><?php echo JText::_('LBL_ITEMS_FILE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::getfiles($this->item->filename, $this->item->release_id, $this->item->id, 'filename', array('onchange'=>'onFileChange();')) ?>
			</div>
		</div>
		<div class="control-group" id="row-url" <?php if($this->item->type != 'link'): ?>style="display:none"<?php endif; ?>>
			<label for="url" class="control-label"><?php echo JText::_('LBL_ITEMS_LINK'); ?></label>
			<div class="controls">
				<input type="text" name="url" id="url" value="<?php echo $this->item->url ?>" onblur="onLinkBlur();">
			</div>
		</div>

		<div class="control-group">
			<label for="filesize" class="control-label"><?php echo JText::_('LBL_ITEMS_FILESIZE'); ?></label>
			<div class="controls">
				<input type="text" name="filesize" id="filesize" value="<?php echo $this->item->filesize ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="md5" class="control-label"><?php echo JText::_('LBL_ITEMS_MD5'); ?></label>
			<div class="controls">
				<input type="text" name="md5" id="md5" value="<?php echo $this->item->md5 ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="sha1" class="control-label"><?php echo JText::_('LBL_ITEMS_SHA1'); ?></label>
			<div class="controls">
				<input type="text" name="sha1" id="sha1" value="<?php echo $this->item->sha1 ?>" >
			</div>
		</div>
		<div class="control-group">
			<label for="hits" class="control-label"><?php echo JText::_('JGLOBAL_HITS'); ?></label>
			<div class="controls">
				<input type="text" name="hits" id="hits" value="<?php echo $this->item->hits ?>">
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="published" class="control-label"><?php echo JText::_('JPUBLISHED'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div class="control-group">
			<label for="access" class="control-label"><?php echo JText::_('JFIELD_ACCESS_LABEL'); ?></label>
			<div class="controls">
				<?php if(version_compare(JVERSION, '3.0', 'gt')): ?>
				<?php
					$options = array(JHtml::_('select.option', '', JText::_('COM_ARS_COMMON_SHOW_ALL_LEVELS')));
					echo JHTML::_('access.level', 'access', $this->item->access, '', $options);
				?>
				<?php else: ?>
				<?php echo JHTML::_('list.accesslevel', $this->item); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if(ArsHelperFiltering::hasAkeebaSubs()): ?>
		<div class="control-group">
			<label for="groups" class="control-label"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_AKEEBA_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::akeebasubsgroups($this->item->groups, 'groups') ?>
			</div>
		</div>
		<?php elseif(defined('PAYPLANS_LOADED')): ?>
		<div class="control-group">
			<label for="groups" class="control-label"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_PAYPLANS_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::payplansgroups($this->item->groups, 'groups') ?>
			</div>
		</div>
		<?php endif; ?>
		<div class="control-group">
			<label for="environments" class="control-label"><?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::environments($this->item->environments); ?>
			</div>
		</div>
		<div class="control-group">
			<label for="updatestream" class="control-label"><?php echo JText::_('LBL_ITEMS_UPDATESTREAM'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::updatestreams($this->item->updatestream, 'updatestream') ?>
			</div>
		</div>
		<div class="control-group">
			<label for="language" class="control-label"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::languages($this->item->language, 'language') ?>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span12">

		<h3><?php echo JText::_('LBL_ARS_ITEMS_DESCRIPTION'); ?></h3>
		<?php echo $editor->display( 'description',  $this->item->description, '97%', '480', '60', '20', array() ) ; ?>

	</div>
</div>
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
						$('#filename').html(data);
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
			var oldAlias = $('#alias').val();
			if(oldAlias == '') {
				var newAlias = basename($('#url').val());
				var qmPos = newAlias.indexOf('?');
				if(qmPos >= 0) {
					newAlias = newAlias.substr(0, qmPos);
				}
				newAlias = newAlias.replace(' ','-');
				newAlias = newAlias.replace('.','-');
				$('#alias').val( newAlias );
			}
		})(akeeba.jQuery);
	}

	function onFileChange()
	{
		(function($){
			var newAlias = basename($('#filename').val());
			newAlias = newAlias.replace(' ','-');
			newAlias = newAlias.replace('.','-');
			$('#alias').val( newAlias );
		})(akeeba.jQuery);
	}
</script>