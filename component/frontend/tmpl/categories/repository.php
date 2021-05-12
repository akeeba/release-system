<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Component\ARS\Site\View\Categories\HtmlView $this */
?>

<div class="item-page<?= $this->escape($this->params->get('pageclass_sfx')) ?>">
	<?php if ($this->params->get('show_page_heading')): ?>
		<div class="page-header">
			<h2>{{{ $this->params->get('page_heading', $this->menu->title) }}}</h2>
		</div>
	<?php endif; ?>

	<?php if ($this->params->get('grouping', 'normal') == 'none'): ?>
		<?= $this->loadAnyTemplate('categories/generic', true, ['section' => 'all', 'title' => '']) ?>
	<?php elseif (!empty($this->customHtmlFile)): ?>
		<?= $this->loadAnyTemplate('categories/customrepo', true, ['renderSection' => 'all', 'title' => '']) ?>
	<?php else: ?>
		<?= $this->loadAnyTemplate('categories/generic', true, [
			'section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL',
		]) ?>
		<?= $this->loadAnyTemplate('categories/generic', true, [
			'section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE',
		]) ?>
	<?php endif; ?>
</div>
