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

/** @var \Akeeba\Component\ARS\Administrator\View\Updatestream\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

$user = Factory::getApplication()->getIdentity() ?: Factory::getUser();
?>

<form action="<?php echo Route::_('index.php?option=com_ars&view=updatestream&layout=edit&id=' . (int) $this->item->id); ?>"
	  aria-label="<?php echo Text::_('COM_ARS_TITLE_UPDATESTREAMS_' . ((int) $this->item->id === 0 ? 'ADD' : 'EDIT'), true); ?>"
	  class="form-validate" id="profile-form" method="post" name="adminForm">

	<div class="card card-block mb-2">
		<div class="card-body">
			<?= $this->form->getField('name')->renderField(); ?>
			<?= $this->form->getField('alias')->renderField(); ?>
			<?= $this->form->getField('category')->renderField(); ?>
			<?= $this->form->getField('type')->renderField(); ?>
			<?= $this->form->getField('element')->renderField(); ?>
			<?= $this->form->getField('packname')->renderField(); ?>
			<?= $this->form->getField('client_id')->renderField(); ?>
			<?= $this->form->getField('folder')->renderField(); ?>
			<?= $this->form->getField('published')->renderField(); ?>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
