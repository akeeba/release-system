<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>
@section('footer')
	<p style="font-size: small" class="akeeba-panel--information">
		<strong>
			Akeeba Release System &bull;
			@sprintf('COM_ARS_CPANEL_COPYRIGHT_LABEL', date('Y'))
		</strong>
		<br />

		@lang('COM_ARS_CPANEL_LICENSE_LABEL')
	</p>
@stop
