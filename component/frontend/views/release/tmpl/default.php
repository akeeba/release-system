<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.utilties.date');

$this->item->hit();

$released = new JDate($this->item->created);
$tabs = array();

?>
<?php if ($this->params->get('show_page_title', 1)) : ?>
	<div class="componentheading<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php endif; ?>

<div class="ars-list-release">
	<h2 class="ars-release-title">
		<img src="<?php echo JURI::base(); ?>/media/com_ars/icons/status_<?php echo $this->item->maturity ?>.png" width="16" height="16" align="left" />
		&nbsp;
		<?php echo $this->escape($this->category->title) ?>
		<?php echo $this->item->version ?>
	</h2>

	<div class="ars-release-properties">
		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_MATURITY') ?>:</span>
			<span class="ars-value">
				<?php echo JText::_('LBL_RELEASES_MATURITY_'.  strtoupper($this->item->maturity)) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_RELEASEDON') ?>:</span>
			<span class="ars-value">
				<?php echo $released->toFormat(JText::_('DATE_FORMAT_LC2')) ?>
			</span>
		</span>

		<span class="ars-release-property">
			<span class="ars-label"><?php echo JText::_('LBL_RELEASES_HITS') ?>:</span>
			<span class="ars-value">
				<?php echo JText::sprintf( ($this->item->hits == 1 ? 'LBL_RELEASES_TIME' : 'LBL_RELEASES_TIMES'), $this->item->hits) ?>
			</span>
		</span>
	</div>

	<div id="reltabs-<?php echo $this->item->id ?>">
		<ul>
			<li>
				<a href="#reltabs-<?php echo $this->item->id ?>-desc">
				<?php echo JText::_('LBL_ARS_RELEASE_DESCRIPTION') ?>
				</a>
			</li>
			<li>
				<a href="#reltabs-<?php echo $this->item->id ?>-notes">
				<?php echo JText::_('LBL_ARS_RELEASE_NOTES') ?>
				</a>
			</li>
		</ul>
		<div id="reltabs-<?php echo $this->item->id ?>-desc" class="ars-release-description">
			<?php echo ArsHelperHtml::preProcessMessage($this->item->description); ?>
		</div>
		<div id="reltabs-<?php echo $this->item->id ?>-notes" class="ars-release-notes">
			<?php echo ArsHelperHtml::preProcessMessage($this->item->notes) ?>
		</div>
	</div>
	<?php $tabs[] = "reltabs-{$this->item->id}"; ?>
</div>

<div class="ars-releases">
<?php if(!count($this->items)) : ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_ITEMS'); ?>
	</div>
<?php else: ?>
	<?php
		foreach($this->items as $item)
		{
			include dirname(__FILE__).DS.'item.php';
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