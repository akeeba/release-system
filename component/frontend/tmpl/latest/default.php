<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\ARS\Site\View\Latest\HtmlView $this */

$menu = Factory::getApplication()->getMenu()->getActive();
$grouping = $this->params->get('grouping', 'normal');

?>
<div class="item-page<?= $this->escape($this->params->get('pageclass_sfx')) ?>>">
	<div class="page-header">
		<h2>
			<?php if ($this->params->get('show_page_heading') && is_object($menu)): ?>
				<?= $this->escape($this->params->get('page_heading', $menu->title)) ?>
			<?php else: ?>
				<?= Text::_('COM_ARS_TITLE_LATEST') ?>
			<?php endif ?>
		</h2>
	</div>

	<?php if ($grouping == 'none'): ?>
		<?= $this->loadAnyTemplate('latest/generic', true, ['section' => 'all', 'title' => '']) ?>
	<?php elseif ($grouping == 'normal'): ?>
		<?= $this->loadAnyTemplate('latest/generic', true, [
			'section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL',
		]) ?>
		<?= $this->loadAnyTemplate('latest/generic', true, [
			'section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE',
		]) ?>
	<?php else: ?>
		<?= $this->loadAnyTemplate('latest/generic', true, [
			'section' => 'bleedingedge', 'title' => 'COM_ARS_CATEGORY_TYPE_BLEEDINGEDGE',
		]) ?>
		<?= $this->loadAnyTemplate('latest/generic', true, [
			'section' => 'normal', 'title' => 'COM_ARS_CATEGORY_TYPE_NORMAL',
		]) ?>
	<?php endif ?>
</div>
