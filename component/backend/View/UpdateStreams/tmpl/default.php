<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

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

<div class="akeeba-block--info">
    <?php echo JText::_('LBL_UPDATESTREAMS_ALLLINKS_INTRO') ?>
    <a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=all&format=xml" target="_blank">
        <?php echo JText::_('LBL_UPDATESTREAMS_ALLLINKS') ?>
    </a>
</div>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form">

    <section class="akeeba-panel--33-66 akeeba-filter-bar-container">
        <div class="akeeba-filter-bar akeeba-filter-bar--left akeeba-form-section akeeba-form--inline">

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="name" placeholder="<?php echo \JText::_('LBL_UPDATES_NAME'); ?>"
                       id="filter_name"
                       value="<?php echo $this->escape($this->filters['name']); ?>"
                       title="<?php echo \JText::_('LBL_UPDATES_NAME'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="element" placeholder="<?php echo \JText::_('LBL_UPDATES_ELEMENT'); ?>"
                       id="filter_element"
                       value="<?php echo $this->escape($this->filters['element']); ?>"
                       title="<?php echo \JText::_('LBL_UPDATES_ELEMENT'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <?php echo Select::updateTypes('type', $this->filters['type'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::categories($this->filters['category'], 'category')?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <?php echo Select::published($this->filters['published'], 'published')?>
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
				<?php echo \JHtml::_('grid.sort', 'LBL_UPDATES_NAME', 'name', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_UPDATES_TYPE', 'type', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'COM_ARS_RELEASES_FIELD_CATEGORY', 'category', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_UPDATES_ELEMENT', 'element', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo JText::_('LBL_UPDATESTREAMS_LINKS')?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'JPUBLISHED', 'published', $this->order_Dir, $this->order, 'browse'); ?>
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
			$i       = 0;
			$enabled = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars');

			foreach($this->items as $row):
				$i++;
				/** @var \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams $row */
				$link = 'index.php?option=com_ars&view=UpdateStream&id='.$row->id;
				?>
                <tr>
                    <td>
						<?php echo \JHtml::_('grid.id', ++$i, $row->id); ?>
                    </td>
                    <td>
						<a href="<?php echo $link; ?>">
                            <?php echo $row->name ?><br/>
                            <span class="small">(<?php echo $row->alias; ?>)</span>
                        </a>
                    </td>
                    <td>
                        <?php echo Html::decodeUpdateType($row->type)?>
                    </td>
                    <td>
						<?php echo $row->categoryObject->title ; ?>
                    </td>
                    <td>
						<?php echo $row->element; ?>
                    </td>
                    <td>
                        <a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&format=ini&id=<?php echo $row->id ?>" target="_blank">INI</a>
                        &bull;
                        <a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=stream&format=xml&id=<?php echo $row->id ?>" target="_blank">XML</a>
                        &bull;
                        <a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=jed&format=xml&id=<?php echo $row->id ?>" target="_blank">JED</a>
                        &bull;
                        <a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=download&format=raw&id=<?php echo $row->id ?>" target="_blank">D/L</a>

                    </td>
                    <td>
						<?php echo JHTML::_('jgrid.published', $row->enabled, $i, '', $enabled, 'cb')?>
                    </td>
                </tr>
			<?php
			endforeach;
		endif; ?>
        </tbody>

    </table>

    <div class="akeeba-hidden-fields-container">
        <input type="hidden" name="option" id="option" value="com_ars"/>
        <input type="hidden" name="view" id="view" value="UpdateStreams"/>
        <input type="hidden" name="boxchecked" id="boxchecked" value="0"/>
        <input type="hidden" name="task" id="task" value="browse"/>
        <input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->escape($this->order); ?>"/>
        <input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->escape($this->order_Dir); ?>"/>
        <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1"/>
    </div>
</form>