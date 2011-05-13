<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.utilities.date');
$model = $this->getModel();
?>
<form name="adminForm" action="index.php" method="POST">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="logs" />
	<input type="hidden" name="task" id="task" value="display" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_ITEM', 'item', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_USER', 'name', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_ACCESSED', 'accessed_on', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_REFERER', 'referer', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_IP', 'ip', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_COUNTRY', 'country', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_LOGS_AUTHORIZED', 'authorized', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::categories($this->lists->fltCategory, 'category', array('onchange'=>'this.form.submit();')) ?>
				<br/>
				<?php echo ArsHelperSelect::releases($this->lists->fltVersion, 'version', array('onchange'=>'this.form.submit();'), $this->lists->fltCategory) ?>
				<br/>
				<input type="text" name="itemtext" id="itemtext"
					value="<?php echo $this->escape($this->lists->fltItemText);?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.itemtext.value='';this.form.submit();">
					<?php echo JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="usertext" id="usertext"
					value="<?php echo $this->escape($this->lists->fltUserText);?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.usertext.value='';this.form.submit();">
					<?php echo JText::_('Reset'); ?>
				</button>
			</td>
			<td>&nbsp;</td>
			<td>
				<input type="text" name="referer" id="referer"
					value="<?php echo $this->escape($this->lists->fltReferer);?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.referer.value='';this.form.submit();">
					<?php echo JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="ip" id="ip"
					value="<?php echo $this->escape($this->lists->fltIP);?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.ip.value='';this.form.submit();">
					<?php echo JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<?php echo ArsHelperSelect::countries($this->lists->fltCountry, 'country', array('onchange'=>'this.form.submit();','style'=>'width: 80px')) ?>
			</td>
			<td>
				<?php echo ArsHelperSelect::booleanlist('authorized', array('onchange'=>'this.form.submit();'), $this->lists->fltAuthorized) ?>
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
				<?php if(version_compare(JVERSION,'1.6.0','gt')): ?>
				<?php echo $item->authorized ? JHTML::_('image','admin/tick.png', '', array('border' => 0), true) : JHTML::_('image','admin/publish_x.png', '', array('border' => 0), true); ?>
				<?php else: ?>
				<img src="<?php echo JURI::base() ?>images/<?php echo $item->authorized ? 'tick.png' : 'publish_x.png'; ?>" border="0" alt="" />
				<?php endif; ?>
			</td>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="8" align="center"><?php echo JText::_('LBL_ARS_NOITEMS') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>