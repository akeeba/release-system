<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Site\View\Items\Html $this */

// Render the filter sidebar
$this->getContainer()->toolbar->setRenderFrontendSubmenu(true);

// Turn off routing before displaying the form to prevent JPagination's call to JRoute from removing the layout and tmpl
// query string parameters which are critical for pagination links to work.
if (!class_exists('ComArsRouter'))
{
	require_once JPATH_COMPONENT . '/Helper/ComArsRouter.php';
}

ComArsRouter::$routeHtml = false;

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

$function = $this->input->getCmd('function', 'arsSelectItem');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form">

    <section class="akeeba-panel--33-66 akeeba-filter-bar-container">
        <div class="akeeba-filter-bar akeeba-filter-bar--left akeeba-form-section akeeba-form--inline">
            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::categories($this->filters['category'], 'category', ['onchange' => 'document.adminForm.submit()'], 0)?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::releases($this->filters['release'], 'release', ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="title" placeholder="<?php echo \JText::_('LBL_VGROUPS_TITLE'); ?>"
                       id="filter_title" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['title']); ?>"
                       title="<?php echo \JText::_('LBL_VGROUPS_TITLE'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::itemType('type', $this->filters['type'], ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::accessLevel('access', $this->filters['access'], ['onchange' => 'document.adminForm.submit()']);?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::published($this->filters['published'], 'published', ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::languages('language', $this->filters['language'], ['onchange' => 'document.adminForm.submit()'])?>
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
				<?php echo JText::_('LBL_ITEMS_CATEGORY'); ?>
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

			/** @var \Akeeba\ReleaseSystem\Admin\Model\Items $row */
			foreach($this->items as $row):
				$type = $row->type == 'link' ? JText::_('LBL_ITEMS_TYPE_LINK') : JText::_('LBL_ITEMS_TYPE_FILE');

				$category_id = Releases::forceEagerLoad($row->release_id, 'category_id');
				$link = 'index.php?option=com_ars&view=Item&id='.$row->id;
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
                        <a href="javascript:arsItemsProxy('<?php echo $row->id?>', '<?php echo $row->title ?>')">
							<?php echo $row->title ?>
                        </a>
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
						<?php echo JHTML::_('jgrid.published', $row->published, $i, '', false, 'cb')?>
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
        <input type="hidden" name="tmpl" value="component"/>
        <input type="hidden" name="layout" value="modal"/>
        <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1"/>
    </div>
</form>
<?php
// Re-enable ARS routing
ComArsRouter::$routeHtml = true;

$function = $this->input->getCmd('function', 'arsSelectItem');
?>
<script type="text/javascript">
	function arsItemsProxy(id, title)
	{
		if (window.parent) window.parent.<?php echo $this->escape($function); ?>(id, title);
	}
</script>
