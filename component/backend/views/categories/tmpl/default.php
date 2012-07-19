<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="categories" />
	<input type="hidden" name="task" id="task" value="browse" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_ARS_CATEGORIES_FIELD_TITLE', 'title', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'LBL_CATEGORIES_VGROUP', 'vgroup_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'COM_ARS_CATEGORIES_FIELD_TYPE', 'type', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<th width="150">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ACCESS_LABEL', 'access', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="80">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language',$this->lists->order_Dir, $this->lists->order); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="text" name="title" id="title"
					value="<?php echo $this->escape($this->getModel()->getState('title'));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button onclick="document.adminForm.title.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
			</td>
			<td>
				<?php echo ArsHelperSelect::vgroups($this->getModel()->getState('vgroup'), 'vgroup', array('onchange'=>'this.form.submit();')); ?>
			</td>
			<td>
				<?php echo ArsHelperSelect::categorytypes($this->getModel()->getState('type'), 'type', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<td></td>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::published($this->getModel()->getState('published'), 'published', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<td></td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="20">
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

			$checkedout = ($item->checked_out != 0);

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
				<?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
			</td>
			<td>
				<a href="index.php?option=com_ars&view=categories&task=edit&id=<?php echo (int)$item->id ?>">
					<?php echo htmlentities($item->title, ENT_COMPAT, 'UTF-8') ?>
				</a>
				<br/>
				<span class="cat-directory-name"><?php echo htmlentities($item->directory, ENT_COMPAT, 'UTF-8') ?></span>
			</td>
			<td>
				<?php echo $item->vgroup_id ? ArsHelperSelect::getVisualGroupName($item->vgroup_id) : '&mdash;'; ?>
			</td>
			<td>
				<span class="category-type-<?php echo $item->type ?>">
				<?php echo JText::_('COM_ARS_CATEGORIES_TYPE_'.  strtoupper($item->type)); ?>
				</span>
			</td>
			<td class="order">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<td>
				<img src="<?php echo $icon ?>" width="16" height="16" align="left" />
				<span class="ars-access">
				&nbsp; 
				<?php echo ArsHelperSelect::renderaccess($item->access); ?>
				</span>
			</td>
			<td>
				<?php echo JHTML::_('grid.published', $item, $i); ?>
			</td>
			<td><?php echo ArsHelperSelect::renderlanguage($item->language) ?></td>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="10" align="center"><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>