<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 *  @license   	GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Akeeba\ReleaseSystem\Admin\Model\Categories;
use Akeeba\ReleaseSystem\Admin\Model\Releases;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Logs\Html */

defined('_JEXEC') or die;

$escapedOrder = addslashes($this->order);
$js = <<< JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
Joomla.orderTable = function () {
	var table = document.getElementById("sortTable");
	var direction = document.getElementById("directionTable");
	var order = table.options[table.selectedIndex].value;
	if (order != '$escapedOrder')
	{
		var dirn = 'asc';
	}
	else
	{
		var dirn = direction.options[direction.selectedIndex].value;
	}
	Joomla.tableOrdering(order, dirn, '');
}

JS;

$this->getContainer()->template->addJSInline($js);

?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form">

	<section class="akeeba-panel--33-66 akeeba-filter-bar-container">
		<div class="akeeba-filter-bar akeeba-filter-bar--left akeeba-form-section akeeba-form--inline">
            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="itemtext" placeholder="<?php echo \JText::_('LBL_LOGS_ITEM'); ?>"
                       id="filter_itemtext" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['itemtext']); ?>"
                       title="<?php echo \JText::_('LBL_LOGS_ITEM'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="usertext" placeholder="<?php echo \JText::_('LBL_LOGS_USER'); ?>"
                       id="filter_usertext" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['usertext']); ?>"
                       title="<?php echo \JText::_('LBL_LOGS_USER'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="referer" placeholder="<?php echo \JText::_('LBL_LOGS_REFERER'); ?>"
                       id="filter_referer" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['referer']); ?>"
                       title="<?php echo \JText::_('LBL_LOGS_REFERER'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="ip" placeholder="<?php echo \JText::_('LBL_LOGS_IP'); ?>"
                       id="filter_ip" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['ip']); ?>"
                       title="<?php echo \JText::_('LBL_LOGS_IP'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <?php echo Select::booleanlist('authorized', ['onchange' => 'document.adminForm.submit()'], $this->filters['authorized'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::categories($this->filters['category'], 'category', ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::releases($this->filters['version'], 'version', ['onchange' => 'document.adminForm.submit()'])?>
            </div>
		</div>

		<div class="akeeba-filter-bar akeeba-filter-bar--right">
			<div class="akeeba-filter-element akeeba-form-group">
				<label for="limit" class="element-invisible">
					<?php echo \JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?>
				</label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>

			<div class="akeeba-filter-element akeeba-form-group">
				<label for="directionTable" class="element-invisible">
					<?php echo \JText::_('JFIELD_ORDERING_DESC'); ?>
				</label>
				<select name="directionTable" id="directionTable" class="input-medium custom-select" onchange="Joomla.orderTable()">
					<option value="">
						<?php echo \JText::_('JFIELD_ORDERING_DESC'); ?>
					</option>
					<option value="asc" <?php echo ($this->order_Dir == 'asc') ? 'selected="selected"' : ""; ?>>
						<?php echo \JText::_('JGLOBAL_ORDER_ASCENDING'); ?>
					</option>
					<option value="desc" <?php echo ($this->order_Dir == 'desc') ? 'selected="selected"' : ""; ?>>
						<?php echo \JText::_('JGLOBAL_ORDER_DESCENDING'); ?>
					</option>
				</select>
			</div>

			<div class="akeeba-filter-element akeeba-form-group">
				<label for="sortTable" class="element-invisible">
					<?php echo \JText::_('JGLOBAL_SORT_BY'); ?>
				</label>
				<select name="sortTable" id="sortTable" class="input-medium custom-select" onchange="Joomla.orderTable()">
					<option value="">
						<?php echo \JText::_('JGLOBAL_SORT_BY'); ?>
					</option>
					<?php echo \JHtml::_('select.options', $this->sortFields, 'value', 'text', $this->order); ?>
				</select>
			</div>
		</div>

	</section>

	<table class="akeeba-table akeeba-table--striped" id="itemsList">
		<thead>
		<tr>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_ITEM', 'item_id', $this->order_Dir, $this->order, 'browse'); ?>
			</th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_USER', 'user_id', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo JText::_('LBL_LOGS_ACCESSED'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_REFERER', 'referer', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_IP', 'ip', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_COUNTRY', 'country', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_LOGS_AUTHORIZED', 'authorized', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="11" class="center">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
		</tfoot>
		<tbody>
		<?php if (!count($this->items)):?>
			<tr>
				<td colspan="11">
					<?php echo JText::_('COM_ARS_COMMON_NOITEMS_LABEL')?>
				</td>
			</tr>
		<?php endif;?>
		<?php
		if ($this->items):
			$i = 0;
			foreach($this->items as $row):
                $i++;
				/** @var \Akeeba\ReleaseSystem\Admin\Model\Logs $row */
				?>
				<tr>
                    <td>
                        <strong><?php echo $this->escape($row->item->release->category->title) ?></strong>
                        <em><?php echo $this->escape($row->item->release->version) ?></em>
                        <br/>
                        <small><?php echo $this->escape($row->item->title) ?></small>
                    </td>
					<td>
						<?php echo Html::renderUserRepeatable($row->user_id)?>
                    </td>
                    <td>
                    <?php
                        $date = new \FOF30\Date\Date($row->accessed_on);
                        echo strftime('%Y-%m-%d', $date->getTimestamp());
                    ?>
                    </td>
                    <td>
                        <?php echo $row->referer; ?>
                    </td>
					<td>
                        <?php echo $row->ip; ?>
					</td>
                    <td>
                        <?php echo Select::countryDecode($row->country)?>
                    </td>
                    <td>
						<?php echo JHTML::_('jgrid.published', $row->authorized, $i, '', false, 'cb')?>
                    </td>
				</tr>
			<?php
			endforeach;
		endif; ?>
		</tbody>

	</table>

	<div class="akeeba-hidden-fields-container">
		<input type="hidden" name="option" id="option" value="com_ars"/>
		<input type="hidden" name="view" id="view" value="Logs"/>
		<input type="hidden" name="boxchecked" id="boxchecked" value="0"/>
		<input type="hidden" name="task" id="task" value="browse"/>
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->escape($this->order); ?>"/>
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->escape($this->order_Dir); ?>"/>
		<input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1"/>
	</div>
</form>