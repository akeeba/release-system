<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var  \Akeeba\Component\ARS\Administrator\View\Controlpanel\HtmlView $this */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>

<?php if ($this->needsMenuItem): ?>
	<details class="alert alert-info">
		<summary class="h3 fs-3 m-0 p-0 text-danger">
			<?= Text::_('COM_ARS_CPANEL_MISSING_CATEGORIES_MENU_HEAD') ?>
		</summary>
		<p>
			<?= Text::_('COM_ARS_CPANEL_MISSING_CATEGORIES_MENU') ?>
		</p>
	</details>
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
