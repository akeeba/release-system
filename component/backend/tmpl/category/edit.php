<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ARS\Administrator\View\Category\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

$user = Factory::getApplication()->getIdentity();

$knownFieldSets  = ['basic', 'permissions'];
$customFieldSets = array_diff(array_keys($this->form->getFieldsets()), $knownFieldSets);
?>

<form action="<?= Route::_('index.php?option=com_ars&view=Category&layout=edit&id=' . (int) $this->item->id) ?>"
	  aria-label="<?= Text::_('COM_ARS_TITLE_CATEGORIES_' . ((int) $this->item->id === 0 ? 'ADD' : 'EDIT'), true) ?>"
	  class="form-validate" id="profile-form" method="post" name="adminForm">

	<?= HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details']); ?>
	<?= HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_ARS_RELEASE_BASIC_LABEL')); ?>
	<div class="row">
		<div class="col-lg">
			<div class="card card-block mb-2">
				<div class="card-body">
					<?= $this->form->getField('title')->renderField(); ?>
					<?= $this->form->getField('alias')->renderField(); ?>
					<?= $this->form->getField('tags')->renderField(); ?>
					<?= $this->form->getField('type')->renderField(); ?>
					<?= $this->form->getField('directory')->renderField(); ?>
					<?= $this->form->getField('published')->renderField(); ?>
					<?= $this->form->getField('access')->renderField(); ?>
					<?= $this->form->getField('show_unauth_links')->renderField(); ?>
					<?= $this->form->getField('redirect_unauth')->renderField(); ?>
					<?= $this->form->getField('is_supported')->renderField(); ?>
					<?= $this->form->getField('created')->renderField(); ?>
					<?= $this->form->getField('created_by')->renderField(); ?>
					<?= $this->form->getField('language')->renderField(); ?>
				</div>
			</div>
		</div>
		<div class="col-lg">
			<div class="card card-block mb-2">
				<div class="card-body">
					<h4>
						<?= Text::_('COM_ARS_RELEASE_DESCRIPTION_LABEL') ?>
					</h4>
					<?= $this->form->getField('description')->renderField([
						'hiddenLabel' => true,
					]); ?>
				</div>
			</div>
		</div>
	</div>

	<?= HTMLHelper::_('uitab.endTab'); ?>

	<?php foreach($customFieldSets as $fieldSet):
		$fieldsInSet = $this->form->getFieldset($fieldSet);
		if (empty($fieldsInSet)) continue;
		?>
		<?= HTMLHelper::_('uitab.addTab', 'myTab', $fieldSet, Text::_($this->form->getFieldsets()[$fieldSet]->label)); ?>
		<?= $this->form->renderFieldset($fieldSet) ?>
		<?= HTMLHelper::_('uitab.endTab'); ?>
	<?php endforeach; ?>

	<?php if ($user->authorise('core.admin', 'com_ars')): ?>
		<?= HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JCONFIG_PERMISSIONS_LABEL')); ?>

		<fieldset id="fieldset-rules" class="options-form">
			<legend><?= Text::_('JCONFIG_PERMISSIONS_LABEL') ?></legend>
			<?= $this->form->getInput('rules') ?>
		</fieldset>

		<?= HTMLHelper::_('uitab.endTab'); ?>
	<?php endif ?>

	<?= HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value="">
	<?= HTMLHelper::_('form.token') ?>
</form>
