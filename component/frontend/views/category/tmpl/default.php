<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>

<div class="item-page<?php echo $this->pparams->get('pageclass_sfx') ?>">
	<?php if ($this->pparams->get('show_page_heading') && $this->pparams->get('show_title')) : ?>
	<div class="page-header">
		<h1> <?php echo $this->escape($this->pparams->get('page_heading')); ?> </h1>
	</div>
	<?php endif;?>

	<?php echo $this->loadAnyTemplate('site:com_ars/browses/category', array('id' => $this->item->id, 'item' => $this->item, 'Itemid' => $this->Itemid, 'no_link' => true)); ?>

<div class="ars-releases">
<?php if(!count($this->items)) : ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_RELEASES'); ?>
	</div>
<?php else: ?>
	<?php
		foreach($this->items as $item)
		{
			echo $this->loadAnyTemplate('site:com_ars/category/release', array('item' => $item, 'Itemid' => $this->Itemid));
		}
	?>
<?php endif; ?>
</div>

<form id="ars-pagination" action="<?php echo JURI::getInstance()->toString() ?>" method="post">
	<input type="hidden" name="option" value="com_ars" />
	<input type="hidden" name="view" value="category" />
	<input type="hidden" name="id" value="<?php echo JRequest::getInt('id',0) ?>" />

<?php if ($this->pparams->get('show_pagination') && ($this->pagination->get('pages.total') > 1)) : ?>
	<div class="pagination">

		<?php if ($this->pparams->get('show_pagination_results')) : ?>
		<p class="counter">
			<?php echo $this->pagination->getPagesCounter(); ?>
		</p>
		<?php endif; ?>

		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>

	<br/>
	<?php echo JText::_('ARS_RELEASES_PER_PAGE') ?>
	<?php echo $this->pagination->getLimitBox(); ?>
<?php endif; ?>
</form>

</div>