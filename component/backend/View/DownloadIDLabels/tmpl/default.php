<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 *  @license   	GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Akeeba\ReleaseSystem\Admin\Model\Categories;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\DownloadIDLabels\Html */

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
                <input type="text" name="label" placeholder="<?php echo \JText::_('COM_ARS_DLIDLABELS_FIELD_LABEL'); ?>"
                       id="filter_label"
                       value="<?php echo $this->escape($this->filters['label']); ?>"
                       title="<?php echo \JText::_('COM_ARS_DLIDLABELS_FIELD_LABEL'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="username" placeholder="<?php echo \JText::_('JGLOBAL_USERNAME'); ?>"
                       id="filter_username"
                       value="<?php echo $this->escape($this->filters['username']); ?>"
                       title="<?php echo \JText::_('JGLOBAL_USERNAME'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="dlid" placeholder="<?php echo \JText::_('COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID'); ?>"
                       id="filter_dlid"
                       value="<?php echo $this->escape($this->filters['dlid']); ?>"
                       title="<?php echo \JText::_('COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID'); ?>"/>
            </div>

			<div class="akeeba-filter-element akeeba-form-group">
                <button class="akeeba-btn--grey akeeba-btn--icon-only akeeba-btn--small akeeba-hidden-phone" onclick="this.form.submit();" title="<?php echo \JText::_('JSEARCH_FILTER_SUBMIT'); ?>">
					<span class="akion-search"></span>
				</button>

				<button id="filter-clear" class="akeeba-btn--grey akeeba-hidden-phone" type="button"
						title="<?php echo \JText::_('JSEARCH_FILTER_CLEAR'); ?>">
					<span class="icon-remove"></span>
				</button>
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
			<th width="32">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>
			</th>
			<th>
				<?php echo \JHtml::_('grid.sort', 'COM_ARS_DLIDLABELS_FIELD_LABEL', 'label', $this->order_Dir, $this->order, 'browse'); ?>
			</th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'JGLOBAL_USERNAME', 'user_id', $this->order_Dir, $this->order, 'browse'); ?>
			</th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'COM_ARS_DLIDLABELS_FIELD_DOWNLOAD_ID', 'dlid', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th width="8%">
				<?php echo \JHtml::_('grid.sort', 'JPUBLISHED', 'enabled', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo JText::_('COM_ARS_DLIDLABELS_FIELD_RESET') ?>
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
		    $user = $this->getContainer()->platform->getUser();

		    /** @var \Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels $row */
			foreach($this->items as $row):
				$link       = 'index.php?option=com_ars&view=DownloadIDLabel&task=edit&id='.$row->ars_dlidlabel_id;
			    $resetLink  = 'index.php?option=com_ars&view=DownloadIDLabels&task=reset&id='.$row->ars_dlidlabel_id;
			    $resetLink .= '&'.$this->getContainer()->platform->getToken(true).'=1';
				$enabled    = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars');

                $label = '<a href="'.$link.'">'.$row->label.'</a>';

				if ($row->label == '_MAIN_' || $row->primary)
                {
                    $label = '<span class="akeeba-label--green">'.JText::_('COM_ARS_DLIDLABELS_LBL_DEFAULT').'</span>';
                }

                $dlprefix = $row->user_id.':';
				$dlclass  = '';

				if ($row->primary)
                {
                    $dlprefix = '';
                    $dlclass  = 'akeeba-label--dark';
                }
				?>
				<tr>
					<td>
                        <?php echo \JHtml::_('grid.id', ++$i, $row->ars_dlidlabel_id); ?>
                    </td>
                    <td>
                        <?php echo $label; ?>
                    </td>
					<td>
                        <?php echo Html::renderUserRepeatable($row->user_id)?>
					</td>
                    <td>
                        <span class="<?php echo $dlclass ?>">
                            <?php echo $dlprefix.$row->dlid ?>
                        </span>
                    </td>
                    <td>
						<?php echo JHTML::_('jgrid.published', $row->enabled, $i, '', $enabled, 'cb')?>
                    </td>
                    <td>
                        <a href="<?php echo $resetLink?>" class="akeeba-btn--orange--small">
                            <span class="akion-refresh"></span>
							<?php echo JText::_('COM_ARS_DLIDLABELS_FIELD_RESET')?>
                        </a>
                    </td>
				</tr>
			<?php
			endforeach;
		endif; ?>
		</tbody>

	</table>

	<div class="akeeba-hidden-fields-container">
		<input type="hidden" name="option" id="option" value="com_ars"/>
		<input type="hidden" name="view" id="view" value="DownloadIDLabels"/>
		<input type="hidden" name="boxchecked" id="boxchecked" value="0"/>
		<input type="hidden" name="task" id="task" value="browse"/>
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->escape($this->order); ?>"/>
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->escape($this->order_Dir); ?>"/>
		<input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1"/>
	</div>
</form>