<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
if (version_compare(JVERSION, '3.0', 'gt'))
{
	JHtml::_('dropdown.init');
	JHtml::_('formbehavior.chosen', 'select');
}

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

$sortFields = array(
	'id'			=> JText::_('JGRID_HEADING_ID'),
	'category'		=> JText::_('LBL_AUTODESC_CATEGORY'),
	'packname'		=> JText::_('LBL_AUTODESC_PACKNAME'),
	'title'			=> JText::_('LBL_AUTODESC_TITLE'),
	'published'		=> JText::_('JPUBLISHED'),
);
?>

<?php if (version_compare(JVERSION, '3.0', 'ge')): ?>
	<script type="text/javascript">
		Joomla.orderTable = function() {
			table = document.getElementById("sortTable");
			direction = document.getElementById("directionTable");
			order = table.options[table.selectedIndex].value;
			if (order != '<?php echo $this->lists->order ?>')
			{
				dirn = 'asc';
			}
			else {
				dirn = direction.options[direction.selectedIndex].value;
			}
			Joomla.tableOrdering(order, dirn);
		}
	</script>
<?php endif; ?>

<div class="row-fluid">
<div class="span12">

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="autodesc" />
	<input type="hidden" name="task" id="task" value="browse" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<?php if(version_compare(JVERSION, '3.0', 'gt')): ?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC') ?></label>
				<?php echo $this->getModel()->getPagination()->getLimitBox(); ?>
			</div>
			<?php
			$asc_sel	= ($this->getLists()->order_Dir == 'asc') ? 'selected="selected"' : '';
			$desc_sel	= ($this->getLists()->order_Dir == 'desc') ? 'selected="selected"' : '';
			?>
			<div class="btn-group pull-right hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC') ?></label>
				<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC') ?></option>
					<option value="asc" <?php echo $asc_sel ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING') ?></option>
					<option value="desc" <?php echo $desc_sel ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING') ?></option>
				</select>
			</div>
			<div class="btn-group pull-right">
				<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY') ?></label>
				<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JGLOBAL_SORT_BY') ?></option>
					<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $this->getLists()->order) ?>
				</select>
			</div>
		</div>
		<div class="clearfix"> </div>
	<?php endif; ?>

<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'LBL_AUTODESC_CATEGORY', 'category', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_AUTODESC_PACKNAME', 'packname', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_AUTODESC_TITLE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="80">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::categories($this->getModel()->getState('category'), 'category', array('onchange'=>'this.form.submit();','class'=>'input-medium')) ?>
			</td>
			<td></td>
			<td class="form-inline">
				<input type="text" name="title" id="title"
					value="<?php echo $this->escape($this->getModel()->getState('title'));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('LBL_AUTODESC_TITLE') ?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.title.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td>
				<?php echo ArsHelperSelect::published($this->getModel()->getState('published'), 'published', array('onchange'=>'this.form.submit();','class'=>'input-medium')) ?>
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="5">
				<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php if($count = count($this->items)): ?>
		<?php
			$i = 0;
			$m = 0;
			foreach($this->items as $item):
			$m = 1 - $m;
		?>
		<tr class="row<?php echo $m ?>">
			<td>
				<?php echo JHTML::_('grid.id', $i, $item->id); ?>
			</td>
			<td>
				<?php echo $this->escape($item->cat_name); ?>
			</td>
			<td>
				<?php echo $this->escape($item->packname); ?>
			</td>
			<td>
				<a href="index.php?option=com_ars&view=autodesc&task=edit&id=<?php echo (int)$item->id ?>">
					<?php echo $this->escape($item->title) ?>
				</a>
			</td>
			<td>
				<?php echo JHTML::_('grid.published', $item, $i); ?>
			</td>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="5" align="center"><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>

</div>
</div>