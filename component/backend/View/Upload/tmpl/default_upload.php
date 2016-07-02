<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\ReleaseSystem\Admin\View\Upload\Html $this */

echo $this->loadAnyTemplate('admin:com_ars/Upload/default');

$i = 1;

$this->addJavascriptFile('media://com_ars/js/qui-helpers.js');

?>

<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('LBL_FOLDERS_LIST'); ?></h3>

		<div id="folderslist">
			<div id="mkfolder" style="clear: both; border: none;">
				<input type="text" size="20" value="" id="newfolder" style="margin: 0;"/>
				<button class="btn btn-mini" id="newfolderbtn">
					<?php echo JText::_('LBL_MKFOLDER'); ?>
				</button>
			</div>
			<div style="clear: both; border: none;"></div>
			<?php if (!is_null($this->parent)): ?>
				<div class="folderrow0 folderrow" title="<?php echo $this->escape($this->parent) ?>">
					<span class="foldername"><?php echo JText::_('LBL_PARENT_FOLDER') ?></span>
				</div>
				<?php $i = 0; endif; ?>
			<?php if (empty($this->folders)): ?>
				<?php echo JText::_('LBL_NO_FOLDERS'); ?>
			<?php else: ?>
				<?php foreach ($this->folders as $folder): $i = 1 - $i; ?>
					<div class="folderrow<?php echo $i ?> folderrow"
					     title="<?php echo $this->escape($this->folder . '/' . $folder) ?>">
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
			<?php if (empty($this->files)): ?>
				<?php echo JText::_('LBL_NO_FILES'); ?>
			<?php else: ?>
				<?php $i = 1;
				foreach ($this->files as $file): ?>
					<?php
					$i        = 1 - $i;
					$filepath = $this->pathToHere . '/' . $file['filename'];
					$filesize = $file['size'];
					?>
					<div class="filerow<?php echo $i ?>">
						<button class="deletefile btn btn-mini" title="<?php echo $this->escape($file['filename']) ?>">
							<span class="icon icon-trash"></span>
						</button>
						<span class="filename"><?php echo $this->escape($file['filename']) ?></span>
						<span class="filesize"><?php echo \Akeeba\ReleaseSystem\Admin\Helper\Format::sizeFormat($filesize) ?></span>
						&nbsp;
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<div style="clear: both;">&nbsp;</div>

<div class="well well-small">
	<form name="fileForm" action="<?php echo JUri::base(); ?>index.php" method="post" enctype="multipart/form-data"
	      class="form form-horizontal">
		<input type="hidden" name="option" value="<?php echo $this->input->getCmd('option') ?>"/>
		<input type="hidden" name="view" value="<?php echo $this->input->getCmd('view') ?>"/>
		<input type="hidden" name="task" value="upload"/>
		<input type="hidden" name="id" value="<?php echo $this->category ?>"/>
		<input type="hidden" name="folder" value="<?php echo $this->escape($this->folder) ?>"/>
		<input type="hidden" id="token" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1"/>
		<input type="hidden" name="format" value="html"/>

		<?php $usize = $this->mediaConfig->get('upload_maxsize') ?>
		<h3><?php echo $usize == 0 ? JText::_('LBL_UPLOAD_FILES_NOLIMIT') : JText::sprintf('LBL_UPLOAD_FILES', (int) $usize); ?></h3>
		<input type="file" name="upload" style="width: auto;" id="upload-file" name="Filedata"/>
		<input class="btn btn-mini" type="submit" id="upload-submit"
		       value="<?php echo JText::_('LBL_START_UPLOAD'); ?>"/>
	</form>
</div>

<script type="text/javascript">
	// Convert divs to queue widgets when the DOM is ready
	(function ($)
	{
		$('#folderslist>div.folderrow').each(function (i, el)
		{
			$(el).click(function ()
			{
				var title = $(this).attr('title');
				$('#folder').val(title);
				$('#adminForm').submit();
			});
		})

		$('#fileslist button.deletefile').each(function (i, el)
		{
			$(el).click(function ()
			{
				var title = $(this).attr('title');
				if (title == '')
				{
					return;
				}
				$('#task').val('delete');
				$('#file').val(title);
				$('#adminForm').submit();
			})
		});

		$('#newfolderbtn').click(function (e)
		{
			var newFolder = $('#newfolder').val();
			$('#task').val('newFolder');
			$('#file').val(newFolder);
			$('#adminForm').submit();
			e.preventDefault();
		});
	})(akeeba.jQuery);
</script>