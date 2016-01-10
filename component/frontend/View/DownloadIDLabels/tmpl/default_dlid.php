<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels  $item  The model */

$prefix = $item->user_id . ':';

if ($item->primary)
{
	$prefix = '';
	$class = 'label label-inverse label-primary';
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