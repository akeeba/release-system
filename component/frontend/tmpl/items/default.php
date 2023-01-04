<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\ARS\Site\View\Items\HtmlView $this */

?>
<div class="item-page<?= $this->escape($this->params->get('pageclass_sfx')) ?>>">
	<?php if ($this->params->get('show_page_heading')): ?>
		<div class="page-header">
			<h2>
				<?= $this->escape($this->params->get('page_heading', $this->menu->title) . ' ' . $this->release->version) ?>
			</h2>
		</div>
	<?php endif ?>

	<?= $this->loadAnyTemplate('items/release', true, [
		'id'   => $this->release->id,
		'item' => $this->release,
	]) ?>

	<div class="ars-items ars-items-<?= $this->category->is_supported ? 'supported' : 'unsupported' ?>">
		<?php if (count($this->items)): ?>
			<?php foreach ($this->items as $item): ?>
				<?= $this->loadAnyTemplate('items/item', true, [
					'item' => $item,
				]) ?>
			<?php endforeach ?>
		<?php else: ?>
			<div class="ars-noitems">
				<?= Text::_('COM_ARS_COMMON_ERR_NO_ITEMS') ?>
			</div>
		<?php endif ?>
	</div>

	<?php if (($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
		<div class="com-ars-releases__pagination w-100">
			<?php if ($this->params->def('show_pagination_results', 1)) : ?>
				<p class="counter float-end pt-3 pe-2">
					<?php echo $this->pagination->getPagesCounter(); ?>
				</p>
			<?php endif; ?>
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	<?php endif; ?>
</div>
