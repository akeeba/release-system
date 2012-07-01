<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

jimport('joomla.utilities.date');

$this->item->hit();

jimport('joomla.filesystem.date');
$released = new JDate($this->item->created);
$tabs = array();

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

?>
<?php if($this->cparams->get('show_page_heading', 1)): ?>
	<h2 class="componentheading<?php echo $this->escape($this->cparams->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->cparams->get('page_title')); ?></h2>
<?php endif; ?>
	
<div class="ars-list-release">
<?php
$item = $this->item;
$item->id = 0;
$params = ArsHelperChameleon::getParams('release');
@ob_start();
echo $this->loadAnyTemplate('site:com_ars/category/release', array('item' => $item));
$contents = ob_get_clean();
$title = "<img src=\"".$base_folder."/media/com_ars/icons/status_".$item->maturity.".png\" width=\"16\" height=\"16\" align=\"left\" />".
	"&nbsp;<span class=\"ars-release-title-version\">".
	$this->escape($item->version)."</span><span class=\"ars-release-title-maturity\">(".
	JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity)).")</span>";
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
		$Itemid = FOFInput::getInt('Itemid', 0, $this->input);
		$Itemid = empty($Itemid) ? "" : "&Itemid=$Itemid";
		foreach($this->items as $item)
		{
			$download_url = AKRouter::_('index.php?option=com_ars&view=download&format=raw&id='.$item->id.$Itemid);
			$title = "<a href=\"$download_url\">".$this->escape($item->title)."</a>";			
			$params = ArsHelperChameleon::getParams('item');
			@ob_start();
			echo $this->loadAnyTemplate('site:com_ars/release/item', array('item' => $item));
			$contents = ob_get_clean();
			$module = ArsHelperChameleon::getModule($title, $contents, $params);
			echo JModuleHelper::renderModule($module, $params);			
		}
	?>
<?php endif; ?>
</div>
	
<form id="ars-pagination" action="index.php?Itemid=<?php FOFInput::getInt('Itemid', 0, $this->input) ?>" method="post">
	<input type="hidden" name="option" value="com_ars" />
	<input type="hidden" name="view" value="release" />
	<input type="hidden" name="id" value="<?php echo $this->release_id ?>" />

<?php if ($this->cparams->get('show_pagination') && ($this->pagination->get('pages.total') > 1)) : ?>
	<div class="pagination">

		<?php if ($this->cparams->get('show_pagination_results')) : ?>
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


<script type="text/javascript">
(function($){
	$(document).ready(function(){
<?php foreach($tabs as $tabid): ?>
	$("#<?php echo $tabid ?>").tabs();
<?php endforeach; ?>
	});
})(akeeba.jQuery);
</script>