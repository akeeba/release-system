<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>
@section('footer')
	<p style="font-size: small" class="well">
		<strong>
			Akeeba Release System {{{ $this->currentVersion }}} &bull;
			@sprintf('COM_ARS_CPANEL_COPYRIGHT_LABEL', date('Y'))
		</strong>
		<br />

		<a href="index.php?option=com_ars&view=Updates&task=force" class="btn btn-inverse btn-small">
			@lang('COM_ARS_CPANEL_MSG_RELOADUPDATE')
		</a>
		<br/>

		@lang('COM_ARS_CPANEL_LICENSE_LABEL')
		<br/>

		<strong>
			If you use Akeeba Release System, please post a rating and a review at the
			<a href="http://extensions.joomla.org/extensions/extension/directory-a-documentation/downloads/akeeba-release-system">Joomla!
				Extensions Directory</a>.
		</strong>
	</p>
@stop