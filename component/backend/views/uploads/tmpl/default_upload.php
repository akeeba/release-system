<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$session  = JFactory::getSession();
$suffix = '&'.$session->getName() .'='. $session->getId();

require_once dirname(__FILE__).'/default.php';
$i = 1;

F0FTemplateUtils::addJS('media://com_ars/js/qui-helpers.js');
F0FTemplateUtils::addCSS('media://com_ars/theme/jquery-ui.css');

require_once JPATH_ROOT.'/components/com_ars/helpers/html.php'

?>

<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('LBL_FOLDERS_LIST'); ?></h3>

		<div id="folderslist">
			<div id="mkfolder" style="clear: both; border: none;">
				<input type="text" size="20" value="" id="newfolder" style="margin: 0;" />
				<button class="btn btn-mini" id="newfolderbtn">
					<?php echo JText::_('LBL_MKFOLDER'); ?>
				</button>
			</div>
			<div style="clear: both; border: none;"></div>
		<?php if(!is_null($this->parent)): ?>
			<div class="folderrow0 folderrow" title="<?php echo $this->escape($this->parent) ?>">
				<span class="foldername"><?php echo JText::_('LBL_PARENT_FOLDER') ?></span>
			</div>
		<?php $i = 0; endif; ?>
		<?php if(empty($this->folders)): ?>
			<?php echo JText::_('LBL_NO_FOLDERS'); ?>
		<?php else: ?>
			<?php foreach($this->folders as $folder): $i = 1 - $i; ?>
			<div class="folderrow<?php echo $i?> folderrow" title="<?php echo $this->escape($this->folder.'/'.$folder) ?>">
				<span class="foldername"><?php echo $this->escape($folder) ?></span>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</div>

	<div class="span6">
		<h3>
			<?php echo JText::_('LBL_FILES_LIST'); ?>
		</h3>
		<div id="fileslist">
		<?php if(empty($this->files)): ?>
			<?php echo JText::_('LBL_NO_FILES'); ?>
		<?php else: ?>
			<?php $i = 1; foreach($this->files as $file): ?>
			<?php
				$i = 1 - $i;
				$filepath = $this->path.'/'.$file['filename'];
				$filesize = $file['size'];
			?>
			<div class="filerow<?php echo $i?>">
				<button class="deletefile btn btn-mini" title="<?php echo $this->escape($file['filename']) ?>">
					<span class="ui-icon ui-icon-trash"></span>
				</button>
				<span class="filename"><?php echo $this->escape($file['filename']) ?></span>
				<span class="filesize"><?php echo ArsHelperHtml::sizeFormat($filesize) ?></span>
				&nbsp;
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</div>
</div>

<div style="clear: both;">&nbsp;</div>

<div class="row-fluid">
<div class="span12">

<!-- File Upload Form -->
<form name="fileForm" action="<?php echo JURI::base(); ?>index.php" method="post" enctype="multipart/form-data" class="form form-horizontal">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="upload" />
	<input type="hidden" name="id" value="<?php echo $this->category ?>" />
	<input type="hidden" name="folder" value="<?php echo $this->escape($this->folder) ?>" />
	<input type="hidden" id="token" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	<input type="hidden" name="format" value="html" />

		<?php $usize = $this->mediaconfig->get('upload_maxsize') ?>
		<h3><?php echo $usize == 0 ? JText::_('LBL_UPLOAD_FILES_NOLIMIT') : JText::sprintf('LBL_UPLOAD_FILES', (int)$usize); ?></h3>
		<input type="file" style="width: auto;" id="upload-file" name="Filedata" />
		<input class="btn btn-mini" type="submit" id="upload-submit" value="<?php echo JText::_('LBL_START_UPLOAD'); ?>"/>
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
</form>

</div>
</div>

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

	$('#fileslist button.deletefile').each(function(i,el){
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