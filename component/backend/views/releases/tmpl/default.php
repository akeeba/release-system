<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.multiselect');

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();
?>

<div class="row-fluid">
<div class="span12">

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="releases" />
	<input type="hidden" name="task" id="task" value="browse" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
<table class="table table-striped" id="itemsList">
	<thead>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<th width="20px">
				<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
			</th>
			<?php endif; ?>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_ARS_RELEASES_FIELD_CATEGORY', 'category_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'COM_ARS_RELEASES_FIELD_VERSION', 'version', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="150">
				<?php echo JHTML::_('grid.sort', 'COM_ARS_RELEASES_FIELD_MATURITY', 'maturity', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<?php endif; ?>
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
				<?php echo JHTML::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language',$this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
		</tr>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<td></td>
			<?php endif; ?>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::categories($this->getModel()->getState('category'), 'category', array('onchange'=>'this.form.submit();','class' => 'input-medium')) ?>
			</td>
			<td></td>
			<td></td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td></td>
			<?php endif; ?>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::published($this->getModel()->getState('published'), 'published', array('onchange'=>'this.form.submit();','class' => 'input-small')) ?>
			</td>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::languages($this->getModel()->getState('language2'), 'language2', array('onchange'=>'this.form.submit();','class' => 'input-small'), true) ?>
			</td>
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
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<td class="order nowrap center hidden-phone">
			<?php if ($this->perms->editstate) :
				$disableClassName = '';
				$disabledLabel	  = '';
				if (!$hasAjaxOrderingSupport['saveOrder']) :
					$disabledLabel    = JText::_('JORDERINGDISABLED');
					$disableClassName = 'inactive tip-top';
				endif; ?>
				<span class="sortable-handler <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>" rel="tooltip">
					<i class="icon-menu"></i>
				</span>
				<input type="text" style="display:none"  name="order[]" size="5"
					value="<?php echo $item->ordering;?>" class="input-mini text-area-order " />
			<?php else : ?>
				<span class="sortable-handler inactive" >
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
				<a href="index.php?option=com_ars&view=releases&task=edit&id=<?php echo (int)$item->id ?>">
					<?php echo htmlentities((empty($item->version) ? '&mdash;' : $item->version), ENT_COMPAT, 'UTF-8') ?>
				</a>
			</td>
			<td>
				<img src="<?php echo $base_folder ?>/media/com_ars/icons/status_<?php echo $item->maturity ?>.png" width="16" height="16" align="left" />
				<span class="ars-access">
					&nbsp;<span class="status-<?php echo $item->maturity?>">
						<?php echo JText::_('COM_ARS_RELEASES_MATURITY_'.  strtoupper($item->maturity)); ?>
					</span>
				</span>
			</td>

			<?php if($hasAjaxOrderingSupport === false): ?>
			<td class="order">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<?php endif; ?>
			
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
			<td colspan="10" align="center"><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>

</div>
</div>