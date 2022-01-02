<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\Component\ARS\Administrator\View\Controlpanel\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

// PHP version warning
$softwareName          = 'Akeeba Release System';
$class_priority_low    = 'alert alert-info';
$class_priority_medium = 'alert alert-warning';
$class_priority_high   = 'alert alert-danger';

require JPATH_ADMINISTRATOR . '/components/com_ars/tmpl/common/phpversion_warning.php';
?>

<?php if ($this->needsMenuItem): ?>
	<div class="alert alert-info">
		<h4>
			<?= Text::_('COM_ARS_CPANEL_MISSING_CATEGORIES_MENU_HEAD') ?>
		</h4>
		<?= Text::_('COM_ARS_CPANEL_MISSING_CATEGORIES_MENU') ?>
	</div>
<?php endif ?>

<div class="row">
	<div class="col">
		<?= $this->loadTemplate('graphs') ?>
	</div>
	<div class="col">
		<?= $this->loadTemplate('icons') ?>
	</div>
</div>

<div>
	<?= $this->loadTemplate('footer') ?>
</div>
