<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
<div class="item-page<?php echo $this->params->get('pageclass_sfx') ?>">
	<?php if ($this->params->get('show_page_heading') && $this->params->get('show_title')) : ?>
	<div class="page-header">
		<h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
	</div>
	<?php endif;?>
	<?php echo $this->loadAnyTemplate('site:com_ars/browses/generic', array('renderSection' => 'normal', 'title' => 'ARS_CATEGORY_NORMAL')); ?>
</div>