<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\Items\Html $this */

?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <h3><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL')?></h3>
        <div class="akeeba-container--50-50">
            <div>
                <div class="akeeba-form-group">
                    <label for="release_id"><?php echo JText::_('LBL_ITEMS_RELEASE'); ?></label>

					<?php echo Select::releases($this->item->release_id, 'release_id')?>
                </div>

                <div class="akeeba-form-group">
                    <label for="title"><?php echo JText::_('LBL_ITEMS_TITLE'); ?></label>

                    <input type="text" name="title" id="title" value="<?php echo $this->escape($this->item->title); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="alias"><?php echo JText::_('COM_ARS_RELEASES_FIELD_ALIAS'); ?></label>

                    <input type="text" name="alias" id="alias" value="<?php echo $this->escape($this->item->alias); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="type"><?php echo JText::_('LBL_ITEMS_TYPE'); ?></label>

					<?php echo Select::itemType('type', $this->item->type, array('onchange' => 'arsItems.onTypeChange();'))?>
                </div>

                <div class="akeeba-form-group">
                    <label for="filename"><?php echo JText::_('LBL_ITEMS_FILE'); ?></label>

                    <select id="filename" name="filename"></select>
                </div>

                <div class="akeeba-form-group">
                    <label for="url"><?php echo JText::_('LBL_ITEMS_LINK'); ?></label>

                    <input type="text" name="url" id="url" value="<?php echo $this->escape($this->item->url); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="filesize"><?php echo JText::_('LBL_ITEMS_FILESIZE'); ?></label>

                    <input type="text" name="filesize" id="filesize" value="<?php echo $this->escape($this->item->filesize); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="md5"><?php echo JText::_('LBL_ITEMS_MD5'); ?></label>

                    <input type="text" name="md5" id="md5" value="<?php echo $this->escape($this->item->md5); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="sha1"><?php echo JText::_('LBL_ITEMS_SHA1'); ?></label>

                    <input type="text" name="sha1" id="sha1" value="<?php echo $this->escape($this->item->sha1); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="hits"><?php echo JText::_('JGLOBAL_HITS'); ?></label>

                    <input type="text" name="hits" id="hits" value="<?php echo $this->escape($this->item->hits); ?>" />
                </div>
            </div>

            <div>
                <div class="akeeba-form-group">
                    <label for="published"><?php echo JText::_('JPUBLISHED'); ?></label>

					<?php echo Select::booleanswitch('published', $this->item->published)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="access"><?php echo JText::_('JFIELD_ACCESS_LABEL'); ?></label>

					<?php echo JHtml::_('access.level', 'access', $this->item->access);?>
                </div>

                <div class="akeeba-form-group">
                    <label for="show_unauth_links"><?php echo JText::_('COM_ARS_COMMON_SHOW_UNAUTH_LINKS'); ?></label>

					<?php echo Select::booleanswitch('show_unauth_links', $this->item->show_unauth_links)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="redirect_unauth"><?php echo JText::_('COM_ARS_COMMON_REDIRECT_UNAUTH'); ?></label>

                    <input type="text" name="redirect_unauth" id="redirect_unauth" value="<?php echo $this->escape($this->item->redirect_unauth); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="groups"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_LABEL'); ?></label>

					<?php echo Select::subscriptionGroups('groups[]', $this->item->groups, array('multiple' => true))?>
                </div>

                <div class="akeeba-form-group">
                    <label for="environments"><?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?></label>

					<?php echo Select::environments('environments[]', $this->item->environments, array('multiple' => true))?>
                </div>

                <div class="akeeba-form-group">
                    <label for="updatestream"><?php echo JText::_('LBL_ITEMS_UPDATESTREAM'); ?></label>

					<?php echo Select::updatestreams('updatestream[]', $this->item->updatestream)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="language"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>

					<?php echo Select::languages('language', $this->item->language)?>
                </div>
            </div>
        </div>

        <div class="akeeba-container--100">
            <div>
				<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))
					->display('description', $this->item->description, '97%', '200', '50', '20', true); ?>
            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="Items" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$this->item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>

<script type="text/javascript">
	var arsItems = {};

	arsItems.onTypeChange = function (e)
	{
		(function ($)
		{
			arsItems.showHideRows();
		})(akeeba.jQuery);
		/**/
	};

	arsItems.populateFiles = function(forceSelected)
	{
		(function ($){
			var itemID = '<?php echo $this->item->id ?>';
			var releaseID = $('#release_id').val();
			var selected = $('#filename').val();

			if (forceSelected)
			{
				selected = forceSelected;
			}

			$.get(
				'index.php',
				{
					'option':     'com_ars',
					'view':       'Ajax',
					'format':     'raw',
					'task':       'getFiles',
					'<?php echo $this->container->platform->getToken(true) ?>':   1,
					'item_id':    itemID,
					'release_id': releaseID,
					'selected':   selected
				},
				function (data, textStatus)
				{
					$('#filename').html(data);
					$('#filename').removeAttr('disabled');
					$('#filename').change(function(e){
						arsItems.onFileChange(e);
					});

					try
					{
						$('#filename').trigger('liszt:updated');
					}
					catch (e)
					{
					}

					arsItems.onFileChange();
				}
			)
		})(akeeba.jQuery);
	};

	arsItems.onLinkBlur = function(e)
	{
		(function ($)
		{
			var oldAlias = $('#alias').val();
			if (oldAlias == '')
			{
				var newAlias = basename($('#url').val());
				var qmPos = newAlias.indexOf('?');

				if (qmPos >= 0)
				{
					newAlias = newAlias.substr(0, qmPos);
				}

				newAlias = newAlias.replace(' ', '-', 'g');
				newAlias = newAlias.replace('.', '-', 'g');

				$('#alias').val(newAlias);
			}
		})(akeeba.jQuery);
	};

	arsItems.onFileChange = function(e)
	{
		(function ($)
		{
			var oldAlias = $('#alias').val();
			if (oldAlias == '')
			{
				var newAlias = basename($('#filename').val());

				newAlias = newAlias.replace(' ', '-', 'g');
				newAlias = newAlias.replace('.', '-', 'g');

				$('#alias').val(newAlias);
			}

		})(akeeba.jQuery);
	};

	arsItems.showHideRows = function(populateFiles)
	{
		(function ($) {
			$('#filename').parent().parent().hide();
			$('#url').parent().parent().hide();

			currentType = $('#type').val();

			if (currentType == 'file')
			{
				$('#filename').parent().parent().show();
				$('#filename').attr('disabled', 'disabled');

				if ((populateFiles === undefined) || populateFiles)
				{
					arsItems.populateFiles();
				}
			}
			else
			{
				$('#url').parent().parent().show();
			}
		})(akeeba.jQuery);
	};

	(function ($){
		$(document).ready(function(){
			$('#url').blur(function(e){
				arsItems.onLinkBlur(e);
			});

			arsItems.showHideRows(false);
			arsItems.populateFiles('<?php echo $this->escape($this->item->filename) ?>');
		})
	})(akeeba.jQuery);
</script>
