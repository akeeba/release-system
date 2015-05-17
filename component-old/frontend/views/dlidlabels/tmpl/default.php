<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var F0FViewForm $this */

JHtml::_('jquery.framework');

$this->loadHelper('filter');

$app = JFactory::getApplication();
$menus = $app->getMenu();
$menu = $menus->getActive();

?>
<?php if ($this->params->get('show_page_heading')) : ?>
	<div class="page-header">
		<h1><?php echo $this->escape($this->params->get('page_heading', $menu->title)); ?></h1>
	</div>
<?php endif;?>

<div class="alert alert-info">
	<?php echo JText::sprintf('COM_ARS_DLIDLABELS_MASTERDLID', ArsHelperFilter::myDownloadID()); ?>
</div>

<div class="nav">
	<button class="btn btn-primary" onclick="Joomla.submitbutton('add'); return false;">
		<span class="icon-white icon-plus-sign"></span>
		<?php echo JText::_('JNew') ?>
	</button>
	<button class="btn btn-danger" onclick="Joomla.submitbutton('remove'); return false;">
		<span class="icon-white icon-minus-sign"></span>
		<?php echo JText::_('JACTION_DELETE') ?>
	</button>
	<button class="btn" onclick="Joomla.submitbutton('publish'); return false;">
		<span class="icon-eye-open"></span>
		<?php echo JText::_('JLIB_HTML_PUBLISH_ITEM') ?>
	</button>
	<button class="btn" onclick="Joomla.submitbutton('unpublish'); return false;">
		<span class="icon-eye-close"></span>
		<?php echo JText::_('JLIB_HTML_UNPUBLISH_ITEM') ?>
	</button>
</div>

<?php
	if (!($this instanceof F0FViewForm))
	{
		JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_ars&view=dlidlabels'));
	}
?>

<?php echo $this->getRenderedForm(); ?>