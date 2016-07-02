<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>
@section('footer')
	<p style="font-size: small" class="well">
		<strong>
			Akeeba Release System &bull;
			@sprintf('COM_ARS_CPANEL_COPYRIGHT_LABEL', date('Y'))
		</strong>
		<br />

		@lang('COM_ARS_CPANEL_LICENSE_LABEL')
	</p>
@stop