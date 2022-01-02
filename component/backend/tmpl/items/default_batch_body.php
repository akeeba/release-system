<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Akeeba\Component\ARS\Administrator\View\Categories\HtmlView $this */

$published = $this->state->get('filter.published');
?>
<div class="container">
	<div class="row">
		<div class="form-group col-md-6">
			<div class="controls">
				<?php echo LayoutHelper::render('joomla.html.batch.language', []); ?>
			</div>
		</div>
		<div class="form-group col-md-6">
			<div class="controls">
				<?php echo LayoutHelper::render('joomla.html.batch.access', []); ?>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="form-group col-md-6">
			<div class="controls">
				<label id="batch-choose-action-lbl" for="batch-release-id">
					<?php echo Text::_('COM_ARS_ITEMS_BATCH_RELEASE_LABEL'); ?>
				</label>
				<div id="batch-choose-action" class="control-group">
					<?=
					HTMLHelper::_(
						'select.groupedlist', $this->get('ReleasesOptions'), "batch[release_id]",
						[
							'list.attr'          => [
								'class' => 'form-select',
							],
							'id'                 => 'batch-release-id',
							'list.select'        => null,
							'group.items'        => null,
							'option.key.toHtml'  => false,
							'option.text.toHtml' => false,
						]
					);
					?>
				</div>
				<div id="batch-copy-move" class="control-group radio">
					<?php echo Text::_('JLIB_HTML_BATCH_MOVE_QUESTION'); ?>
					<?php echo HTMLHelper::_('select.radiolist', [
						HTMLHelper::_('select.option', 'c', Text::_('JLIB_HTML_BATCH_COPY')),
						HTMLHelper::_('select.option', 'm', Text::_('JLIB_HTML_BATCH_MOVE')),
					], 'batch[move_copy]', '', 'value', 'text', 'm'); ?>
				</div>
			</div>
		</div>
	</div>

</div>
