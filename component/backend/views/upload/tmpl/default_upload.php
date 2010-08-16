<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
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
	<div id="folderswidget" class="ui-widget ui-corner-all">
		<div id="foldersheader" class="ui-widget-header ui-corner-top">
			<?php echo JText::_('LBL_FOLDERS_LIST'); ?>
		</div>
		<div id="folderslist" class="ui-widget-content ui-corner-bottom">
			<div id="mkfolder">
				<input type="text" size="20" value="" id="newfolder" />
				<span class="ui-state-default" id="newfolderbtn">
					<?php echo JText::_('LBL_MKFOLDER'); ?>
				</span>
			</div>
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
	</div>

	<div id="fileswidget" class="ui-widget ui-corner-all">
		<div id="filesheader" class="ui-widget-header ui-corner-top">
			<?php echo JText::_('LBL_FILES_LIST'); ?>
		</div>
		<div id="fileslist" class="ui-widget-content ui-corner-bottom">
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
				<span class="ui-icon ui-icon-trash deletefile" title="<?php echo $this->escape($file) ?>">
				</span>
				<span class="filename"><?php echo $this->escape($file) ?></span>
				<span class="filesize"><?php echo ArsHelperHtml::sizeFormat($filesize) ?></span>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</div>
</div>
<div class="clr"></div>

<form name="fileForm" id="fileForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="finish" />
	<input type="hidden" name="id" value="<?php echo $this->category ?>" />
	<input type="hidden" name="folder" value="<?php echo $this->escape($this->folder) ?>" />
	<input type="hidden" id="token" name="<?php echo JUtility::getToken();?>" value="1" />

	<div id="uploader">
		<p><?php echo JText::_('ERR_NO_UPLOAD_SUPPORT'); ?></p>
	</div>
	<input type="Submit" value="<?php echo JText::_('upload') ?>" />
</form>

<script type="text/javascript">
// Convert divs to queue widgets when the DOM is ready
(function($){
	$("#uploader").pluploadQueue({
		// General settings
		runtimes : 'gears,flash,silverlight,browserplus,html5,html4',
		url : 'index.php?option=com_ars&view=upload&task=save<?php echo $suffix ?>',
		max_file_size : '10mb',
		<?php if($this->chunking): ?>
		chunk_size: '250kb',
		<?php endif; ?>
		unique_names : true,

		// Resize images on clientside if we can
		//resize : {width : 320, height : 240, quality : 90},

		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg,gif,png"},
			{title : "Zip files", extensions : "zip"},
			{title : "Tar files", extensions : "tar,tgz,gz"},
			{title : "PDF files", extensions : "pdf"},
			{title : "JPA/JPS files", extensions : "jpa,jps"}
		],

		// Flash settings
		flash_swf_url : '<?php echo JURI::base() ?>../media/com_ars/js/plupload.flash.swf',

		// Silverlight settings
		silverlight_xap_url : '<?php echo JURI::base() ?>../media/com_ars/js/plupload.silverlight.xap'
	});

	// Client side form validation
	$('#fileForm').submit(function(e) {
		var uploader = $('#uploader').pluploadQueue();

		// Validate number of uploaded files
		if (uploader.total.uploaded == 0) {
			// Files in queue upload them first
			if (uploader.files.length > 0) {
				// When all files are uploaded submit form
				uploader.bind('UploadProgress', function() {
					if (uploader.total.uploaded == uploader.files.length)
						$.get(
							'index.php?option=com_ars&view=upload&task=token<?php echo $suffix ?>',
							'',
							function (data, textStatus, XMLHttpRequest){
								var junk = null;
								var message = "";
								var valid_pos = data.indexOf('###');
								if( valid_pos == -1 ) {
									e.preventDefault();
									return;
								} else if( valid_pos != 0 ) {
									junk = data.substr(0, valid_pos);
									message = data.substr(valid_pos);
								} else {
									message = data;
								}
								message = message.substr(3); // Remove triple hash in the beginning
								var valid_pos = message.lastIndexOf('###');
								message = message.substr(0, valid_pos); // Remove triple hash in the end

								$('#token').attr('name',message);
								$('#fileForm').submit();
							},
							'text/plain'
						);
				});

				uploader.start();
			} else
				alert('You must at least upload one file.');

			e.preventDefault();
		}
	});

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
