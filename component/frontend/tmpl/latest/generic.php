<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Latest\HtmlView;
use Joomla\CMS\Language\Text;

/**
 * @var  HtmlView    $this
 * @var  string      $section
 * @var  string|null $title
 */
?>
<div class="ars-categories-<?= $section ?>">
	<?php if (!empty($title)): ?>
		<h3 class="h2 my-4 border-bottom border-muted">
			<?= Text::_($title) ?>
		</h3>
	<?php endif ?>

	<?php if (empty($this->categories)): ?>
		<p class="muted ars-no-items">
			<?= Text::_('COM_ARS_COMMON_ERR_NO_CATEGORIES') ?>
		</p>
	<?php else: ?>
		<?php foreach ($this->categories as $item): ?>
			<?php if (($section === 'all') || ($item->type == $section)): ?>
				<?= $this->loadAnyTemplate('latest/category', true, [
					'category' => $item,
				]) ?>
			<?php endif ?>
		<?php endforeach; ?>
	<?php endif ?>
</div>
