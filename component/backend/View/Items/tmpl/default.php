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

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Items\Html */

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
				<?php echo Select::categories($this->filters['category'], 'category', array(), 0)?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::releases($this->filters['release'], 'release')?>
            </div>

			<div class="akeeba-filter-element akeeba-form-group">
				<input type="text" name="title" placeholder="<?php echo \JText::_('LBL_VGROUPS_TITLE'); ?>"
					   id="filter_title"
					   value="<?php echo $this->escape($this->filters['title']); ?>"
					   title="<?php echo \JText::_('LBL_VGROUPS_TITLE'); ?>"/>
			</div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::itemType('type', $this->filters['type'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo JHtml::_('access.level', 'access', $this->filters['access']);?>
            </div>

			<div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::published($this->filters['published'], 'published')?>
			</div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::languages('language', $this->filters['language'])?>
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
            <th width="20px">
                <a href="#" onclick="Joomla.tableOrdering('ordering','asc','');return false;" class="hasPopover" title="" data-content="Select to sort by this column" data-placement="top" data-original-title="Ordering"><i class="icon-menu-2"></i></a>
            </th>
			<th width="32">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>
			</th>
			<th>
				<?php echo \JHtml::_('grid.sort', 'LBL_ITEMS_CATEGORY', 'category', $this->order_Dir, $this->order, 'browse'); ?>
			</th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_ITEMS_RELEASE', 'release', $this->order_Dir, $this->order, 'browse'); ?>
			</th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_ITEMS_TITLE', 'title', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'LBL_ITEMS_TYPE', 'type', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'JFIELD_ACCESS_LABEL', 'access', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th width="8%">
				<?php echo \JHtml::_('grid.sort', 'JPUBLISHED', 'published', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'JGLOBAL_HITS', 'hits', $this->order_Dir, $this->order, 'browse'); ?>
            </th>
            <th>
				<?php echo \JHtml::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language', $this->order_Dir, $this->order, 'browse'); ?>
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
			foreach($this->items as $row):
                $type = $row->type == 'link' ? JText::_('LBL_ITEMS_TYPE_LINK') : JText::_('LBL_ITEMS_TYPE_FILE');

			    $category_id = Releases::forceEagerLoad($row->release_id, 'category_id');
				$edit = $user->authorise('core.admin') || $user->authorise('core.edit', 'com_ars.category.'.$category_id);
				$link = 'index.php?option=com_ars&view=Item&id='.$row->id;

				$enabled = $this->container->platform->getUser()->authorise('core.edit.state', 'com_ars')
				?>
				<tr>
                    <td>
                        <?php echo Html::ordering($this, 'ordering', $row->ordering)?>
                    </td>
					<td>
                        <?php echo \JHtml::_('grid.id', ++$i, $row->id); ?>
                    </td>
                    <td>
                        <?php echo Categories::forceEagerLoad($category_id, 'title'); ?>
                    </td>
                    <td>
                        <?php echo Releases::forceEagerLoad($row->release_id, 'version') ?>
                    </td>
					<td>
						<?php if ($edit):?>
                            <a href="<?php echo $link?>">
								<?php echo $row->title ?>
                            </a>
						<?php else:?>
							<?php echo $row->title ?>
						<?php endif;?>
					</td>
                    <td>
                        <?php echo $type ?>
                    </td>
                    <td>
                    <?php
                        foreach ($row->environments as $environment)
                        {
                            echo Select::environmentIcon($environment);
                        }
                    ?>
                    </td>
                    <td>
                        <?php echo Html::accessLevel($row->access) ?>
                    </td>
                    <td>
						<?php echo JHTML::_('jgrid.published', $row->published, $i, '', $enabled, 'cb')?>
                    </td>
                    <td>
                        <?php echo $row->hits; ?>
                    </td>
                    <td>
                        <?php echo Html::language($row->language) ?>
                    </td>
				</tr>
			<?php
			endforeach;
		endif; ?>
		</tbody>

	</table>

	<div class="akeeba-hidden-fields-container">
		<input type="hidden" name="option" id="option" value="com_ars"/>
		<input type="hidden" name="view" id="view" value="Items"/>
		<input type="hidden" name="boxchecked" id="boxchecked" value="0"/>
		<input type="hidden" name="task" id="task" value="browse"/>
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->escape($this->order); ?>"/>
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->escape($this->order_Dir); ?>"/>
		<input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1"/>
	</div>
</form>