<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels  $item  The model */

$prefix = $item->user_id . ':';

if ($item->primary)
{
	$prefix = '';
	$class = 'akeeba-label--grey';
}
else
{
	$class = '';
}

$text = $prefix . $item->dlid;
?>
<span class="<?php echo $class ?>">
	<?php echo $text ?>
</span>
