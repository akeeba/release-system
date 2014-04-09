<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHtml::_('behavior.multiselect');

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');

$this->loadHelper('select');

F0FTemplateUtils::addJS('media://com_ars/js/gui-helpers.js');
F0FTemplateUtils::addCSS('media://com_ars/css/backend.css');

?>

<div class="row-fluid">
<div class="span12">

	<h3><?php echo JText::_('COM_ARS_COMMON_SELECT_RELEASE_LABEL') ?></h3>

	<div class="form form-horizontal">
		<div class="control-group">
			<label for="arsrelease" class="control-label"><?php echo JText::_('LBL_IMPJED_SELECTRELEASE'); ?></label>
			<div class="controls">
				<?php echo ArsHelperSelect::releases(null, 'arsrelease', array('class' => 'input-medium')) ?>
			</div>
		</div>
	</div>

	<h3><?php echo JText::_('LBL_IMPJED_JCSETUP') ?></h3>

	<div class="form form-horizontal">
		<div id="setup-project" class="control-group">
			<label for="jcproject" class="control-label"><?php echo JText::_('LBL_IMPJED_JCPROJECT_TITLE'); ?></label>
			<div class="controls">
				<input type="text" size="20" id="jcproject" value="" />
				<button class="btn btn-mini" onclick="" id="getPackages"><?php echo JText::_('LBL_IMPJED_GETPACKAGES_TITLE'); ?></button>
			</div>
		</div>
		<div id="setup-package" class="control-group">
			<label for="jcpackage" class="control-label"><?php echo JText::_('LBL_IMPJED_JCPACKAGE_TITLE'); ?></label>
			<div class="controls">
				<select id="jcpackage"></select>
				<button class="btn btn-mini" onclick="" id="getReleases"><?php echo JText::_('LBL_IMPJED_GETRELEASES_TITLE'); ?></button>
			</div>
		</div>
		<div id="setup-release" class="control-group">
			<label for="jcrelease" class="control-label"><?php echo JText::_('LBL_IMPJED_JCRELEASE_TITLE'); ?></label>
			<div class="controls">
				<select id="jcrelease"></select>
				<button class="btn btn-mini" onclick="" id="getFiles"><?php echo JText::_('LBL_IMPJED_GETFILES_TITLE'); ?></button>
			</div>
		</div>
		<div id="setup-files" class="control-group">
			<label for="jcfiles" class="control-label"><?php echo JText::_('LBL_IMPJED_JCFILES_TITLE'); ?></label>
			<span id="jcfiles"></span>
		</div>
	</div>

</div>
</div>

<script type="text/javascript">
(function($){
	$(document).ready(function(){
		// Joomla! 3.0-beta1 workaround - Y U CHANGE ME SELECTBOX?!!
		// Even crazier: this behaviour was REMOVED a few days after the beta
		// was released. Why do I bother with alpha quality software?
		if($('#jcpackage_chzn') !== undefined) {
			$('#jcpackage_chzn').remove();
			$('#jcpackage').css('display', 'inline');
		}
		if($('#jcrelease_chzn') !== undefined) {
			$('#jcrelease_chzn').remove();
			$('#jcrelease').css('display', 'inline');
		}

		// Hide details
		$('#setup-package').hide();
		$('#setup-release').hide();
		$('#setup-files').hide();

		$('#jcproject').keyup(function(e){
			if(e.keyCode == 13) {
				$('#getPackages').trigger('click');
			}
		});

		$('#jcproject').blur(function(e){
			$('#getPackages').trigger('click');
		});

		$('#jcpackage').change(function(e){
			$('#getReleases').trigger('click');
		});

		$('#jcrelease').change(function(e){
			$('#getFiles').trigger('click');
		});

		$('#getPackages').click(function(e){
			// Hide details
			$('#setup-package').hide();
			$('#setup-release').hide();
			$('#setup-files').hide();

			doAjax({
				'task': 'jcpackages',
				'project': $('#jcproject').val()
			}, function(data){
				$('#jcpackage').html('');
				$.each(data,function(i, pack){
					$(document.createElement('option'))
						.attr('value', pack)
						.text(pack)
						.appendTo($('#jcpackage'));
				});
				$('#setup-package').show();
			});
		});

		$('#getReleases').click(function(e){
			// Hide details
			$('#setup-release').hide();
			$('#setup-files').hide();

			doAjax({
				'task': 'jcreleases',
				'project': $('#jcproject').val(),
				'package': $('#jcpackage').val()
			}, function(data){
				$('#jcrelease').html('');
				$.each(data,function(i, pack){
					$(document.createElement('option'))
						.attr('value', pack)
						.text(pack)
						.appendTo($('#jcrelease'));
				});
				$('#setup-release').show();
			});
		});

		$('#getFiles').click(function(e){
			// Hide details
			$('#setup-files').hide();

			doAjax({
				'task': 'jcfiles',
				'project': $('#jcproject').val(),
				'package': $('#jcpackage').val(),
				'release': $('#jcrelease').val()
			}, function(data){
				$('#jcfiles').html('');
				$.each(data,function(i, pack){
					$(document.createElement('div'))
						.addClass('filetoimport')
						.text(i)
						.attr('title',pack)
						.click(function(e){
							var release = $('#arsrelease').val();
							var url = $(this).attr('title');

							if( (release == '') || (release == 0) ) {
								alert('<?php echo JText::_('ERR_IMPJED_MUSTSELECTRELEASE') ?>');
								return;
							}

							doAjax({
								'task':		'import',
								'release':	release,
								'url':		url
							},function(data){
								if(data == true) {
									$(e.target)
										.removeClass('filetoimport')
										.addClass('filedone')
										.unbind('click');
								} else {
									alert(data);
								}
							});
						})
						.appendTo($('#jcfiles'));
				});
				$('#jcfiles').attr('disabled','');
				$('#setup-files').show();
			});
		});

	});
})(akeeba.jQuery);

/**
 * Performs an AJAX request and returns the parsed JSON output.
 * @param data An object with the query data, e.g. a serialized form
 * @param successCallback A function accepting a single object parameter, called on success
 * @param errorCallback A function accepting a single string parameter, called on failure
 */
function doAjax(data, successCallback, errorCallback, useCaching)
{
	(function($) {
		$.blockUI({ message: '<h1><img src="<?php echo $base_folder ?>/media/com_ars/theme/images/throbber.gif" /> <?php echo JText::_('COM_ARS_CPANEL_WORKING_LABEL') ?></h1>' });
		var structure =
		{
			type: "POST",
			url: '<?php echo 'index.php?option=com_ars&view=impjed&format=raw' ?>',
			cache: false,
			data: data,
			timeout: 600000,
			success: function(msg) {
				$.unblockUI();
				// Initialize
				var junk = null;
				var message = "";

				// Get rid of junk before the data
				var valid_pos = msg.indexOf('###');
				if( valid_pos == -1 ) {
					return;
				} else if( valid_pos != 0 ) {
					// Data is prefixed with junk
					junk = msg.substr(0, valid_pos);
					message = msg.substr(valid_pos);
				}
				else
				{
					message = msg;
				}
				message = message.substr(3); // Remove triple hash in the beginning

				// Get of rid of junk after the data
				var valid_pos = message.lastIndexOf('###');
				message = message.substr(0, valid_pos); // Remove triple hash in the end

				try {
					var data = JSON.parse(message);
				} catch(err) {
					var msg = err.message + "\n\n" + message + "\n";
					alert(msg);
					return;
				}

				// Call the callback function
				successCallback(data);
			},
			error: function(Request, textStatus, errorThrown) {
				$.unblockUI();
				var message = 'AJAX Loading Error\nHTTP Status: '+Request.status+' ('+Request.statusText+')\n';
				message = message + 'Internal status: '+textStatus+'\n';
				message = message + 'XHR ReadyState: ' + Request.readyState + '\n\n';
				message = message + 'Raw server response:\n'+Request.responseText;
				alert(message);
			}
		};
		$.ajax( structure );
	})(akeeba.jQuery);
}
</script>