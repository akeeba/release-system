<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

/**
 * These variables are extracted from the indexed array returned by the
 * \Joomla\Module\Atsgraphs\Administrator\Dispatcher\Dispatcher::getLayoutData() method.
 *
 * @var stdClass                 $module   The module data loaded by Joomla
 * @var AdministratorApplication $app      The Joomla administrator application object
 * @var Input                    $input    The application input object
 * @var Registry                 $params   The module parameters
 * @var stdClass                 $template The current admin template
 * @var bool                     $hasArs   Is ARS installed and enabled?
 *
 * @see \Joomla\Module\Atsgraphs\Administrator\Dispatcher\Dispatcher::getLayoutData()
 */
?>

<?php // Display a message if com_ars is unpublished or not installed. ?>
<?php if (!$hasArs): ?>
	<div class="alert alert-danger">
		<h4 class="alert-header">
			<?= Text::_('MOD_ARSGRAPH_ERR_ARSMISSING_HEAD') ?>
		</h4>
		<p>
			<?= Text::_('MOD_ARSGRAPH_ERR_ARSMISSING_BODY') ?>
		</p>
	</div>
<?php else: ?>
	<div class="card">
		<h3 class="card-header">
			<?= Text::_('COM_ARS_CPANEL_DLSTATSMONTHLY_LABEL') ?>
		</h3>
		<div class="card-body">
			<canvas id="mdrChart" width="400" height="200"></canvas>
		</div>
	</div>
<?php endif; ?>