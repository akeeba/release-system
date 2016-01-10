<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels  $model  The model */
/** @var  int  $fieldValue  The ID of the UpdateStreams item */
/** @var  \FOF30\Form\Form  $form  The form being rendered */
/** @var  \FOF30\Form\FieldInterface  $fieldElement  The form field being rendered */

$prefix = $model->user_id . ':';

if ($model->primary)
{
	$prefix = '';
	$class = 'label label-inverse';
}
else
{
	$class = '';
}

$text = $prefix . $model->dlid;
?>
<span class="<?php echo $class ?>">
	<?php echo $text ?>
</span>