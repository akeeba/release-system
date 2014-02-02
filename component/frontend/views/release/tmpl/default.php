<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.utilities.date');

FOFTemplateUtils::addCSS('media://com_ars/css/frontend.css');

$this->item->hit();
$results    = false;
$released   = new JDate($this->item->created);
$userAccess = JFactory::getUser()->getAuthorisedViewLevels();

$app = JFactory::getApplication();
$menus = $app->getMenu();
$menu = $menus->getActive();
$pageHeading = $this->pparams->get('page_heading', $menu->title) . ' ' . $this->item->version;

?>
<div class="item-page<?php echo $this->pparams->get('pageclass_sfx') ?>">
	<?php if ($this->pparams->get('show_page_heading')) : ?>
		<div class="page-header">
			<h1><?php echo $this->escape($pageHeading); ?></h1>
		</div>
	<?php endif;?>

	<?php echo $this->loadAnyTemplate('site:com_ars/category/release', array('item' => $this->item, 'Itemid' => $this->Itemid, 'no_link' => true)); ?>

<div class="ars-releases">
<?php if(!count($this->items)) : ?>
	<div class="ars-noitems">
		<?php echo JText::_('ARS_NO_ITEMS'); ?>
	</div>
<?php else: ?>
	<?php
		foreach($this->items as $item)
		{
			$output = $this->loadAnyTemplate('site:com_ars/release/item', array('item'   => $item,
			                                                                    'Itemid' => $this->Itemid,
																		        'userAccess' => $userAccess));
			if($output)
			{
				$results = true;
			}
			echo $output;
		}

		if(!$results)
		{
	?>
		<div class="ars-noitems">
			<?php echo JText::_('ARS_NO_ITEMS'); ?>
		</div>
	<?php
		}
	?>
<?php endif; ?>
</div>

<form id="ars-pagination" action="<?php echo JURI::getInstance()->toString() ?>" method="post">
	<input type="hidden" name="option" value="com_ars" />
	<input type="hidden" name="view" value="release" />
	<input type="hidden" name="id" value="<?php echo $this->release_id ?>" />

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