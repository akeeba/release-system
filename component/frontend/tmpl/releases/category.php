<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Releases\HtmlView;

/**
 * @var  HtmlView $this
 * @var  object   $item
 * @var  int      $id
 */

?>
<div class="ars-category-<?= $id ?> ars-category-<?= $item->is_supported ? 'supported' : 'unsupported' ?>">
	<h3 class="text-muted mb-4">
		<?= $this->escape($item->title) ?>
	</h3>
</div>

