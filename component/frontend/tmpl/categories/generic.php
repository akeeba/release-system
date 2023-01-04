<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Categories\HtmlView;
use Joomla\CMS\Language\Text;

/**
 * @var  HtmlView $this
 * @var string    $section
 * @var string    $tile
 */
?>
<div class="ars-categories-<?= $section ?> mb-4">
	<?php if (!empty($title)): ?>
		<div class="page-header">
			<h2 class="border-bottom border-2">
				<?= Text::_($title) ?>
			</h2>
		</div>
	<?php endif; ?>

	<?php if (empty($this->items)): ?>
		<p class="muted ars-no-items alert alert-warning">
			<?= Text::_('COM_ARS_COMMON_ERR_NO_CATEGORIES') ?>
		</p>
	<?php else:
		foreach ($this->items as $id => $item)
		{
			if (($item->type == $section) || ($section == 'all'))
			{
				echo $this->loadAnyTemplate('categories/category', true, [
					'id'     => $id,
					'item'   => $item,
					'Itemid' => $this->Itemid,
				]);
			}
		}
	endif; ?>
</div>
