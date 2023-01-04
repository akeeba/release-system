<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Component\ARS\Site\View\Categories\HtmlView $this */
?>
<div class="item-page<?= $this->escape($this->params->get('pageclass_sfx')) ?>">
	<?php if($this->params->get('show_page_heading')): ?>
		<div class="page-header">
			<h1>
				<?= $this->escape($this->params->get('page_heading', $this->menu->title)) ?>
			</h1>
		</div>
	<?php endif; ?>

	<?= $this->loadAnyTemplate('categories/generic', true, ['section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE']) ?>
</div>