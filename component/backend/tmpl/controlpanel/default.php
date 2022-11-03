<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\Component\ARS\Administrator\View\Controlpanel\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

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
