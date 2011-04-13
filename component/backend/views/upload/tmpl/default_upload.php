<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

$session  =& JFactory::getSession();
$suffix = '&'.$session->getName() .'='. $session->getId();

require_once dirname(__FILE__).DS.'default.php';
$i = 1;
?>

<div id="contentsbrowser">
	<fieldset id="folderswidget">
		<legend><?php echo JText::_('LBL_FOLDERS_LIST'); ?></legend>
		
		<div id="folderslist">
			<div id="mkfolder">
				<input type="text" size="20" value="" id="newfolder" />
				<button id="newfolderbtn">
					<?php echo JText::_('LBL_MKFOLDER'); ?>
				</button>
			</div>
			<div style="clear: both;"></div>
		<?php if(!is_null($this->parent)): ?>
			<div class="folderrow0 folderrow" title="<?php echo $this->escape($this->parent) ?>">
				<span class="foldername"><?php echo JText::_('LBL_PARENT_FOLDER') ?></span>
			</div>
		<?php $i = 0; endif; ?>
		<?php if(empty($this->folders)): ?>
			<?php echo JText::_('LBL_NO_FOLDERS'); ?>
		<?php else: ?>
			<?php foreach($this->folders as $folder): $i = 1 - $i; ?>
			<div class="folderrow<?php echo $i?> folderrow" title="<?php echo $this->escape($folder) ?>">
				<span class="foldername"><?php echo $this->escape($folder) ?></span>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>		
	</fieldset>

	<fieldset id="fileswidget">
		<legend>
			<?php echo JText::_('LBL_FILES_LIST'); ?>
		</legend>
		<div id="fileslist">
		<?php if(empty($this->files)): ?>
			<?php echo JText::_('LBL_NO_FILES'); ?>
		<?php else: ?>
			<?php $i = 1; foreach($this->files as $file): ?>
			<?php
				$i = 1 - $i;
				$filepath = $this->path.DS.$file;
				$filesize = @filesize($filepath);
			?>
			<div class="filerow<?php echo $i?>">
				<button class="deletefile" title="<?php echo $this->escape($file) ?>">
					<span class="ui-icon ui-icon-trash"></span>
				</button>
				<span class="filename"><?php echo $this->escape($file) ?></span>
				<span class="filesize"><?php echo ArsHelperHtml::sizeFormat($filesize) ?></span>
				&nbsp;
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</fieldset>
</div>
<div class="clr"></div>

<!-- File Upload Form -->
<form name="fileForm" action="<?php echo JURI::base(); ?>index.php" method="post" enctype="multipart/form-data">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="upload" />
	<input type="hidden" name="id" value="<?php echo $this->category ?>" />
	<input type="hidden" name="folder" value="<?php echo $this->escape($this->folder) ?>" />
	<input type="hidden" id="token" name="<?php echo JUtility::getToken();?>" value="1" />
	<input type="hidden" name="format" value="html" />

	<fieldset id="uploadform">
		<?php $usize = (version_compare(JVERSION,'1.6.0','ge')) ? $this->config->get('upload_maxsize') : $this->config->get('upload_maxsize') / 1048756 ?>
		<legend><?php echo $usize == 0 ? JText::_('LBL_UPLOAD_FILES_NOLIMIT') : JText::sprintf('LBL_UPLOAD_FILES', (int)$usize); ?></legend>
		<fieldset id="upload-noflash" class="actions">
			<label for="upload-file" class="hidelabeltxt"><?php echo JText::_('LBL_UPLOAD_FILE'); ?></label>
			<input type="file" id="upload-file" name="Filedata" />
			<label for="upload-submit" class="hidelabeltxt"><?php echo JText::_('LBL_START_UPLOAD'); ?></label>
			<input type="submit" id="upload-submit" value="<?php echo JText::_('LBL_START_UPLOAD'); ?>"/>
		</fieldset>
		<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
		<div id="upload-flash" class="hide">
			<ul>
				<li><a href="#" id="upload-browse"><?php echo JText::_('LBL_BROWSE_FILES'); ?></a></li>
				<li><a href="#" id="upload-clear"><?php echo JText::_('LBL_CLEAR_LIST'); ?></a></li>
				<li><a href="#" id="upload-start"><?php echo JText::_('LBL_START_UPLOAD'); ?></a></li>
			</ul>
			<div class="clr"> </div>
			<p class="overall-title"></p>
			<?php echo JHTML::_('image','media/bar.gif', JText::_('LBL_OVERALL_PROGRESS'), array('class' => 'progress overall-progress'), true); ?>
			<div class="clr"> </div>
			<p class="current-title"></p>
			<?php echo JHTML::_('image','media/bar.gif', JText::_('LBL_CURRENT_PROGRESS'), array('class' => 'progress current-progress'), true); ?>
			<p class="current-text"></p>
		</div>
		<ul class="upload-queue" id="upload-queue">
			<li style="display:none;"></li>
		</ul>
		<?php endif; ?>
	</fieldset>
</form>

<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
(function($){
	$('#folderslist>div.folderrow').each(function(i,el){
		$(el).click(function(){
			var title = $(this).attr('title');
			$('#folder').val(title);
			$('#adminForm').submit();
		});
	})

	$('#fileslist span.deletefile').each(function(i,el){
		$(el).click(function(){
			var title = $(this).attr('title');
			if(title == '') return;
			$('#task').val('delete');
			$('#file').val(title);
			$('#adminForm').submit();
		})
	});

	$('#newfolderbtn').click(function(e){
		var newFolder = $('#newfolder').val();
		$('#task').val('newfolder');
		$('#file').val(newFolder);
		$('#adminForm').submit();
		e.preventDefault();
	});
})(akeeba.jQuery);
</script>