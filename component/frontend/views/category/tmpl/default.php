<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$tabs = array();

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

?>
<?php if($this->pparams->get('show_page_heading', 1)): ?>
	<h2 class="componentheading<?php echo $this->escape($this->pparams->get('pageclass_sfx')); ?>"><?php echo $this->escape($this->pparams->get('page_title')); ?></h2>
<?php endif; ?>

<?php
	$item = $this->item; $item->id = 0;
	$params = ArsHelperChameleon::getParams('category');
	@ob_start();
	echo $this->loadAnyTemplate('site:com_ars/browses/category', array('item' => $item));
	$contents = ob_get_clean();
	$module = ArsHelperChameleon::getModule($item->title, $contents, $params);
	echo JModuleHelper::renderModule($module, $params);
?>

<div class="ars-releases">
<?php if(!count($this->items)) : ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_RELEASES'); ?>
	</div>
<?php else: ?>
	<?php
		foreach($this->items as $item)
		{
			$params = ArsHelperChameleon::getParams('release');
			@ob_start();
			echo $this->loadAnyTemplate('site:com_ars/category/release', array('item' => $item));
			$contents = ob_get_clean();
			$Itemid = FOFInput::getInt('Itemid', 0, $this->input);
			$release_url = AKRouter::_('index.php?option=com_ars&view=release&id='.$item->id.'&Itemid='.$Itemid);
			
			$title = "<img src=\"".$base_folder."/media/com_ars/icons/status_".$item->maturity.".png\" width=\"16\" height=\"16\" align=\"left\" />".
				"&nbsp;	<a href=\"".$release_url."\"><span class=\"ars-release-title-version\">".
				$this->escape($item->version)."</span><span class=\"ars-release-title-maturity\">(".
				JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity)).")</span></a>";
			$module = ArsHelperChameleon::getModule($title, $contents, $params);
			echo JModuleHelper::renderModule($module, $params);
		}
	?>
<?php endif; ?>
</div>

<form id="ars-pagination" action="index.php?Itemid=<?php echo $Itemid ?>" method="post">
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