<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Helper\TagsHelper;

defined('_JEXEC') || die;

/**
 * @var \Joomla\CMS\MVC\View\HtmlView $this
 * @var object                        $item
 * @var string                        $type
 */

$tagsHelper = new TagsHelper();
$tags = $tagsHelper->getItemTags($type, $item->id);

if (empty($tags))
{
	return;
}
?>
<div class="ars-category-tags d-flex gap-1 mt-1">
	<?php foreach ($tags as $tag): ?>
	<div class="badge bg-dark small px-1">
		<?= $tag->title ?>
	</div>
	<?php endforeach;?>
</div>
