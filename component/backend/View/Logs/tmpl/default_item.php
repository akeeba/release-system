<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\Model\Logs  $model  The model */
?>

<strong><?php echo $this->escape($model->item->release->category->title) ?></strong>
<em><?php echo $this->escape($model->item->release->version) ?></em>
<br/>
<small><?php echo $this->escape($model->item->title) ?></small>
