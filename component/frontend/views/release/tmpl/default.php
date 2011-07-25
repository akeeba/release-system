<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.utilities.date');

$this->item->hit();

$released = new JDate($this->item->created);
$tabs = array();

?>
<?php if ($this->params->get('show_page_title', 1)) : ?>
	<div class="componentheading<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php endif; ?>

<div class="ars-list-release">
<?php
$item = $this->item;
$item->id = 0;
$params = ArsHelperChameleon::getParams('release');
@ob_start();
@include $this->getSubLayout('release','category');
$contents = ob_get_clean();
$title = "<img src=\"".JURI::base()."/media/com_ars/icons/status_".$item->maturity.".png\" width=\"16\" height=\"16\" align=\"left\" />".
	"&nbsp;<span class=\"ars-release-title-version\">".
	$this->escape($item->version)."</span><span class=\"ars-release-title-maturity\">(".
	JText::_('LBL_RELEASES_MATURITY_'.  strtoupper($item->maturity)).")</span>";
$module = ArsHelperChameleon::getModule($title, $contents, $params);
echo JModuleHelper::renderModule($module, $params);
?>
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
			$Itemid = JRequest::getInt('Itemid',0);
			$Itemid = empty($Itemid) ? "" : "&Itemid=$Itemid";
			$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid);
			$title = "<a href=\"$download_url\">".$this->escape($item->title)."</a>";			
			$params = ArsHelperChameleon::getParams('item');
			@ob_start();
			@include $this->getSubLayout('item');
			$contents = ob_get_clean();
			$module = ArsHelperChameleon::getModule($title, $contents, $params);
			echo JModuleHelper::renderModule($module, $params);			
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