<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var F0FViewHtml $this */

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

$model = $this->getModel();

$base_folder = rtrim(JURI::base(), '/');

if (substr($base_folder, -13) == 'administrator')
{
	$base_folder = rtrim(substr($base_folder, 0, -13), '/');
}

$this->loadHelper('select');

F0FTemplateUtils::addCSS('media://com_ars/css/backend.css');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();

$sortFields = array(
	'id'          => JText::_('JGRID_HEADING_ID'),
	'ordering'    => JText::_('JFIELD_ORDERING_LABEL'),
	'category_id' => JText::_('LBL_ITEMS_CATEGORY'),
	'release_id'  => JText::_('LBL_ITEMS_RELEASE'),
	'title'       => JText::_('LBL_ITEMS_TITLE'),
	'type'        => JText::_('LBL_ITEMS_TYPE'),
	'access'      => JText::_('JFIELD_ACCESS_LABEL'),
	'published'   => JText::_('JPUBLISHED'),
	'hits'        => JText::_('JGLOBAL_HITS'),
	'language'    => JText::_('JFIELD_LANGUAGE_LABEL'),
);

?>

<script type="text/javascript">
	Joomla.orderTable = function ()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $this->lists->order ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn);
	}
</script>

<div class="row-fluid">
<div class="span12">

<form name="adminForm" id="adminForm" action="index.php" method="post">
<input type="hidden" name="option" id="option" value="com_ars"/>
<input type="hidden" name="view" id="view" value="items"/>
<input type="hidden" name="task" id="task" value="browse"/>
<input type="hidden" name="boxchecked" id="boxchecked" value="0"/>
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0"/>
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>"/>
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>"/>
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1"/>

	<div id="filter-bar" class="btn-toolbar">
		<div class="btn-group pull-right hidden-phone">
			<label for="limit"
			       class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC') ?></label>
			<?php echo $this->getModel()->getPagination()->getLimitBox(); ?>
		</div>
		<?php
		$asc_sel = ($this->getLists()->order_Dir == 'asc') ? 'selected="selected"' : '';
		$desc_sel = ($this->getLists()->order_Dir == 'desc') ? 'selected="selected"' : '';
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
	<div class="clearfix"></div>

<table class="table table-striped" id="itemsList">
	<thead>
	<tr>
		<?php if ($hasAjaxOrderingSupport !== false): ?>
			<th width="20px">
				<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
				<a href="javascript:saveorder(<?php echo count($this->items) - 1 ?>, 'saveorder')" rel="tooltip"
				   class="btn btn-micro pull-right" title="<?php echo JText::_('JLIB_HTML_SAVE_ORDER') ?>">
					<span class="icon-ok"></span>
			</th>
		<?php endif; ?>
		<th width="20">
			<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>
		</th>
		<th width="160">
			<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_CATEGORY', 'category_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th width="100">
			<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_RELEASE', 'release_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th>
			<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_TITLE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th width="100">
			<?php echo JHTML::_('grid.sort', 'LBL_ITEMS_TYPE', 'type', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<?php if ($hasAjaxOrderingSupport === false): ?>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
		<?php endif; ?>
		<th>
			<?php echo JText :: _('LBL_ITEMS_ENVIRONMENTS'); ?>
		</th>
		<th width="150">
			<?php echo JHTML::_('grid.sort', 'JFIELD_ACCESS_LABEL', 'access', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th width="80">
			<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th width="80">
			<?php echo JHTML::_('grid.sort', 'JGLOBAL_HITS', 'hits', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
		<th>
			<?php echo JHTML::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
		</th>
	</tr>
	<tr>
		<?php if ($hasAjaxOrderingSupport !== false): ?>
			<td></td>
		<?php endif; ?>
		<td></td>
		<td>
			<?php echo ArsHelperSelect::categories($this->getModel()
														->getState('category'), 'category', array('onchange' => 'this.form.submit();', 'class' => 'input-medium')) ?>
		</td>
		<td>
			<?php echo ArsHelperSelect::releases($this->getModel()
													  ->getState('release'), 'release', array('onchange' => 'this.form.submit();', 'class' => 'input-medium'), $this->getModel()
																																									->getState('category')) ?>
		</td>
		<td></td>
		<td></td>
		<?php if ($hasAjaxOrderingSupport === false): ?>
			<td></td>
		<?php endif; ?>
		<td></td>
		<td></td>
		<td>
			<?php echo ArsHelperSelect::published($this->getModel()
													   ->getState('published'), 'published', array('onchange' => 'this.form.submit();', 'class' => 'input-small')) ?>
		</td>
		<td></td>
		<td>
			<?php echo ArsHelperSelect::languages($this->getModel()
													   ->getState('language2'), 'language2', array('onchange' => 'this.form.submit();', 'class' => 'input-small'), true) ?>
		</td>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="20">
			<?php if ($this->pagination->total > 0)
			{
				echo $this->pagination->getListFooter();
} ?>
		</td>
	</tr>
	</tfoot>
	<tbody>
	<?php if ($count = count($this->items)): ?>
		<?php
		$i = 0;
		$m = 1;

		foreach ($this->items as $item):
			$m = 1 - $m;

			$checkedout = ($item->checked_out != 0);
			$ordering = $this->lists->order == 'ordering';

			if (!is_array($item->environments))
			{
				$item->environments = json_decode($item->environments);
			}

			// This is a stupid requirement of JHTML. Go figure!
			switch ($item->access)
			{
				case 0:
					$item->groupname = JText::_('public');
					break;
				case 1:
					$item->groupname = JText::_('registered');
					break;
				case 2:
					$item->groupname = JText::_('special');
					break;
			}

			$icon = $base_folder . '/media/com_ars/icons/' . (empty($item->groups) ? 'unlocked_16.png' : 'locked_16.png');
			?>
			<tr class="row<?php echo $m ?>">
				<?php if ($hasAjaxOrderingSupport !== false): ?>
					<td class="order nowrap center hidden-phone">
						<?php if ($this->perms->editstate) :
							$disableClassName = '';
							$disabled = '';
							$disabledLabel = '';
							if (!$hasAjaxOrderingSupport['saveOrder']) :
								$disabledLabel = JText::_('JORDERINGDISABLED');
								$disabled = 'disabled="disabled"';
								$disableClassName = 'inactive tip-top';
							endif; ?>
							<span class="sortable-handler <?php echo $disableClassName ?>"
								  title="<?php echo $disabledLabel ?>" rel="tooltip">
					<i class="icon-menu"></i>
				</span>
							<input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
								   class="input-mini text-area-order" <?php echo $disabled ?> />
						<?php else : ?>
							<span class="sortable-handler inactive">
					<i class="icon-menu"></i>
				</span>
						<?php endif; ?>
					</td>
				<?php endif; ?>
				<td>
					<?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
				</td>
				<td>
					<?php echo htmlentities($item->cat_title, ENT_COMPAT, 'UTF-8') ?>
				</td>
				<td>
					<?php echo htmlentities($item->version, ENT_COMPAT, 'UTF-8') ?>
				</td>
				<td>
					<a href="index.php?option=com_ars&view=items&task=edit&id=<?php echo (int)$item->id ?>">
						<?php echo empty($item->title) ? '&mdash;&mdash;&mdash;' : htmlentities($item->title, ENT_COMPAT, 'UTF-8') ?>
					</a>
				</td>
				<td>
					<?php echo JText::_('LBL_ITEMS_TYPE_' . strtoupper($item->type)); ?>
				</td>

				<?php if ($hasAjaxOrderingSupport === false): ?>
					<td class="order">
						<span><?php echo $this->pagination->orderUpIcon($i, true, 'orderup', 'Move Up', $ordering); ?></span>
						<span><?php echo $this->pagination->orderDownIcon($i, $count, true, 'orderdown', 'Move Down', $ordering); ?></span>
						<?php $disabled = $ordering ? '' : 'disabled="disabled"'; ?>
						<input type="text" name="order[]" size="5"
							   value="<?php echo $item->ordering; ?>" <?php echo $disabled ?> class="text_area"
							   style="text-align: center"/>
					</td>
				<?php endif; ?>
				<td>
					<?php if (is_string($item->environments))
					{
						$item->environments = json_decode($item->environments);
					} ?>
					<?php if (!empty($item->environments))
					{
						foreach ($item->environments as $eid)
						{
							echo ArsHelperSelect::environmenticon($eid) . '<br/>';
						}
					} ?>
				</td>
				<td>
					<img src="<?php echo $icon ?>" width="16" height="16" align="left"/>
				<span class="ars-access">
				&nbsp;
					<?php echo ArsHelperSelect::renderaccess($item->access); ?>
				</span>
				</td>
				<td>
					<?php echo JHTML::_('grid.published', $item, $i); ?>
				</td>
				<td>
					<?php echo (int)$item->hits ?>
				</td>
				<td><?php echo ArsHelperSelect::renderlanguage($item->language) ?></td>
			</tr>
			<?php
			$i++;
		endforeach;
		?>
	<?php else : ?>
		<tr>
			<td colspan="11" align="center"><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>

</div>
</div>