<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;

/** @var \Akeeba\Component\ARS\Site\View\Dlidlabel\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

$user = Factory::getApplication()->getIdentity();
?>

<div class="card card-body mb-3">
	<?= Toolbar::getInstance()->render(); ?>
</div>

<form action="<?php echo Route::_('index.php?option=com_ars&view=dlidlabel&layout=edit&id=' . (int) $this->item->id); ?>"
	  aria-label="<?php echo Text::_('COM_ARS_TITLE_DLIDLABELS_' . ((int) $this->item->id === 0 ? 'ADD' : 'EDIT'), true); ?>"
	  class="form-validate" id="profile-form" method="post" name="adminForm">

	<div class="card card-block mb-2">
		<div class="card-body">
			<?= $this->form->getField('id')->renderField(); ?>
			<?= $this->form->getField('title')->renderField(); ?>
			<?= $this->form->getField('published')->renderField(); ?>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="returnurl" value="<?= base64_encode($this->returnURL) ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
