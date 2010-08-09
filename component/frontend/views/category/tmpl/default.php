<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$tabs = array();
?>
<?php if ($this->params->get('show_page_title', 1)) : ?>
	<div class="componentheading<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php endif; ?>

<div class="ars-list-category">
	<h2 class="ars-category-title">
		<?php echo $this->item->title ?>
	</h2>
	<div class="ars-category-description">
		<?php echo ArsHelperHtml::preProcessMessage($this->item->description) ?>
	</div>
</div>

<div class="ars-releases">
<?php if(!count($this->items)) : ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_RELEASES'); ?>
	</div>
<?php else: ?>
	<?php
		foreach($this->items as $item)
		{
			include dirname(__FILE__).DS.'release.php';
		}
	?>
<?php endif; ?>
</div>

<form id="ars-pagination" action="index.php?Itemid=<?php echo JRequest::getInt('Itemid',0) ?>" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="id" value="<?php echo JRequest::getInt('id',0) ?>" />

<?php if ($this->params->get('show_pagination')) : ?>
	<?php echo $this->pagination->getPagesLinks(); ?><br/>
	<?php echo JText::_('ARS_RELEASES_PER_PAGE') ?>
	<?php echo $this->pagination->getLimitBox(); ?>
<?php endif; ?>
<?php if ($this->params->get('show_pagination_results')) : ?>
	<br/><?php echo $this->pagination->getPagesCounter(); ?>
<?php endif; ?>
</form>


<script type="text/javascript">
(function($){
	$(document).ready(function(){
<?php foreach($tabs as $tabid): ?>
	$("#<?php echo $tabid ?>").tabs();
<?php endforeach; ?>
	});
})(akeeba.jQuery);
</script>