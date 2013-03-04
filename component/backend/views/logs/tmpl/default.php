<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.utilities.date');

$this->loadHelper('select');

FOFTemplateUtils::addCSS('media://com_ars/css/backend.css');
?>

<div class="row-fluid">
<div class="span12">

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="logs" />
	<input type="hidden" name="task" id="task" value="browse" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_ITEM', 'item', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_USER', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_ACCESSED', 'accessed_on', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_REFERER', 'referer', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_IP', 'ip', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_COUNTRY', 'country', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_AUTHORIZED', 'authorized', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td class="form-inline">
				<?php echo ArsHelperSelect::categories($this->getModel()->getState('category'), 'category', array('onchange'=>'this.form.submit();','class'=>'input-medium')) ?>
				<br/>
				<?php echo ArsHelperSelect::releases($this->getModel()->getState('version'), 'version', array('onchange'=>'this.form.submit();','class'=>'input-medium'), $this->getModel()->getState('category')) ?>
				<br/>
				<input type="text" name="itemtext" id="itemtext"
					value="<?php echo $this->escape($this->getModel()->getState('itemtext'));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('LBL_LOGS_ITEM') ?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.itemtext.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td class="form-inline">
				<input type="text" name="usertext" id="usertext"
					value="<?php echo $this->escape($this->getModel()->getState('usertext'));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('LBL_LOGS_USER') ?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.usertext.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td>&nbsp;</td>
			<td class="form-inline">
				<input type="text" name="referer" id="referer"
					value="<?php echo $this->escape($this->getModel()->getState('referer'));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('LBL_LOGS_REFERER') ?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.referer.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td class="form-inline">
				<input type="text" name="ip" id="ip"
					value="<?php echo $this->escape($this->getModel()->getState('ip'));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('LBL_LOGS_IP') ?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.ip.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td>
				<?php echo ArsHelperSelect::countries($this->getModel()->getState('country'), 'country', array('onchange'=>'this.form.submit();','class'=>'input-medium')) ?>
			</td>
			<td>
				<?php echo ArsHelperSelect::booleanlist('authorized', array('onchange'=>'this.form.submit();','class'=>'input-mini'), $this->getModel()->getState('authorized')) ?>
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="8">
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
		?>
		<tr class="row<?php echo $m?>">
			<td>
				<?php echo JHTML::_('grid.id', $i, $item->id, false); ?>
			</td>
			<td>
				<?php echo $this->escape($item->category) ?>
				<?php echo $this->escape($item->version) ?> <br/>
				<small><?php echo $this->escape($item->item) ?></small>
			</td>
			<td>
				<strong><?php echo $this->escape($item->name) ?></strong><br/>
				<small>
					<?php echo $this->escape($item->username) ?> &bull;
					<?php echo $this->escape($item->email) ?>
				</small>
			</td>
			<td>
				<?php echo $this->escape($item->accessed_on) ?>
			</td>
			<td>
				<div style="width: 200px; overflow: hidden;">
					<a href="<?php echo $this->escape($item->referer) ?>" target="_blank">
						<?php echo $this->escape($item->referer, -15) ?>
					</a>
				</div>
			</td>
			<td>
				<tt><?php echo $this->escape($item->ip); ?></tt>
			</td>
			<td>
				<?php echo $this->escape(ArsHelperSelect::decodeCountry($item->country)) ?>
			</td>
			<td>
				<?php echo $item->authorized ? JHTML::_('image','admin/tick.png', '', array('border' => 0), true) : JHTML::_('image','admin/publish_x.png', '', array('border' => 0), true); ?>
			</td>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="8" align="center"><?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>
	
</div>
</div>