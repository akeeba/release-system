<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */

echo $this->getRenderedForm();

?>
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
					'<?php echo \JFactory::getSession()->getFormToken() ?>':   1,
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
					})

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
			})

			arsItems.showHideRows(false);
			arsItems.populateFiles('<?php echo $this->escape($this->item->filename) ?>');
		})
	})(akeeba.jQuery);
</script>