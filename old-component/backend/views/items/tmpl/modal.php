<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$model = $this->getModel();

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

$function	= JRequest::getCmd('function', 'arsSelectItem');
?>
<form name="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="items" />
	<input type="hidden" name="task" id="task" value="display" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_CATEGORY', 'category_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_RELEASE', 'release_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_TITLE', 'title', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<th>
				<?php echo JHTML::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language',$this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<?endif;?>
		</tr>
		<tr>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::categories($this->lists->fltCategory, 'category', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<td>
				<?php echo ArsHelperSelect::releases($this->lists->fltRelease, 'release', array('onchange'=>'this.form.submit();'), $this->lists->fltCategory) ?>
			</td>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<td></td>
			<?endif;?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="10">
				<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php if($count = count($this->items)): ?>
		<?php
			$i = 0;
			$m = 1;

			foreach($this->items as $item):
			$m = 1 - $m;

			$checkedout = $item->checked_out > 0;
			$ordering = $this->lists->order == 'ordering';

			// This is a stupid requirement of JHTML. Go figure!
			switch($item->access) {
				case 0: $item->groupname = JText::_('public'); break;
				case 1: $item->groupname = JText::_('registered'); break;
				case 2: $item->groupname = JText::_('special'); break;
			}

			$icon = $base_folder.'/media/com_ars/icons/' . (empty($item->groups) ? 'unlocked_16.png' : 'locked_16.png');
		?>
		<tr class="row<?php echo $m?>">
			<td>
				<?php echo htmlentities($item->cat_title, ENT_COMPAT, 'UTF-8') ?>
			</td>
			<td>
				<?php echo htmlentities($item->cat_title, ENT_COMPAT, 'UTF-8') ?>
			</td>
			<td>
				<a class="pointer" onclick="if (window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>');" href="#">
					<?php echo empty($item->title) ? '&mdash;&mdash;&mdash;' : htmlentities($item->title, ENT_COMPAT, 'UTF-8') ?>
				</a>
			</td>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<td><?php echo $item->language ?></td>
			<?endif;?>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="10" align="center"><?php echo JText::_('LBL_ARS_NOITEMS') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>