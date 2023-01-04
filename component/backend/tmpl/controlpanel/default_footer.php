<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="mt-3 p-3 bg-light border-top border-4 d-flex flex-column">
	<p class="text-muted">
		<?= Text::_('COM_ARS') ?> &bull;
		<?= Text::sprintf('COM_ARS_CPANEL_COPYRIGHT_LABEL', date('Y')) ?>

		<br />

		<?= Text::_('COM_ARS_CPANEL_LICENSE_LABEL') ?>
	</p>
</div>
