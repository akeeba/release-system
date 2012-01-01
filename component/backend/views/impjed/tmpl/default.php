<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');
$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        
?>

<fieldset id="setup-ars">
	<legend><?php echo JText::_('LBL_RELEASES_SELECT') ?></legend>
	<div id="setup-ars-mainbody">
		<label for="arsrelease"><?php echo JText::_('LBL_IMPJED_SELECTRELEASE') ?></label>
		<?php echo ArsHelperSelect::releases(null, 'arsrelease') ?>
	</div>
</fieldset>

<fieldset>
	<legend id="setup-header"><?php echo JText::_('LBL_IMPJED_JCSETUP') ?></legend>
	
	<div id="setup-mainbody">
		<div id="setup-project">
			<label for="jcproject"><?php echo JText::_('LBL_IMPJED_JCPROJECT_TITLE') ?></label>
			<input type="text" size="20" id="jcproject" value="" />
			<button onclick="" id="getPackages"><?php echo JText::_('LBL_IMPJED_GETPACKAGES_TITLE'); ?></button>
		</div>
		<div id="setup-package">
			<label for="jcpackage"><?php echo JText::_('LBL_IMPJED_JCPACKAGE_TITLE') ?></label>
			<select id="jcpackage"></select>
			<button onclick="" id="getReleases"><?php echo JText::_('LBL_IMPJED_GETRELEASES_TITLE'); ?></button>
		</div>
		<div id="setup-release">
			<label for="jcrelease"><?php echo JText::_('LBL_IMPJED_JCRELEASE_TITLE') ?></label>
			<select id="jcrelease"></select>
			<button onclick="" id="getFiles"><?php echo JText::_('LBL_IMPJED_GETFILES_TITLE'); ?></button>
		</div>
		<div id="setup-files">
			<label for="jcfiles"><?php echo JText::_('LBL_IMPJED_JCFILES_TITLE') ?></label>
			<span id="jcfiles"></span>
		</div>
	</div>	
</fieldset>

<script type="text/javascript">
(function($){
	$(document).ready(function(){
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
		$.blockUI({ message: '<h1><img src="<?php echo $base_folder ?>/media/com_ars/theme/images/throbber.gif" /> <?php echo JText::_('ARS_WORKING_MESSAGE') ?></h1>' });
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