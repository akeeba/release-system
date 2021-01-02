<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Akeeba\ReleaseSystem\Site\Model\Categories;
use Akeeba\ReleaseSystem\Site\Model\Releases;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Site\View\Items\Html $this */

// Render the filter sidebar
$this->getContainer()->toolbar->setRenderFrontendSubmenu(true);

$user = $this->getContainer()->platform->getUser();

?>
@jhtml('behavior.core')
@jhtml('formbehavior.chosen')

<form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form">

    <section class="akeeba-panel--33-66 akeeba-filter-bar-container">
        <div class="akeeba-filter-bar akeeba-filter-bar--left akeeba-form-section akeeba-form--inline">
            <div class="akeeba-filter-element akeeba-form-group">
                @selectfilter('category', \Akeeba\ReleaseSystem\Admin\Helper\Select::categories(), 'COM_ARS_COMMON_CATEGORY_SELECT_LABEL')
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                @selectfilter('release', \Akeeba\ReleaseSystem\Admin\Helper\Select::releases(), 'COM_ARS_COMMON_SELECT_RELEASE_LABEL', ['class' => 'advancedSelect'])
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                @searchfilter('title', 'title', 'LBL_ITEMS_TITLE')
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                @selectfilter('type', \Akeeba\ReleaseSystem\Admin\Helper\Select::itemType(false), 'LBL_ITEMS_TYPE_SELECT')
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                @selectfilter('access', \Akeeba\ReleaseSystem\Admin\Helper\Select::accessLevel(), 'COM_ARS_COMMON_SHOW_ALL_LEVELS', ['class' => 'advancedSelect'])
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                {{ \FOF30\Utils\FEFHelper\BrowseView::publishedFilter('published', 'JPUBLISHED') }}
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                @selectfilter('language', \Akeeba\ReleaseSystem\Admin\Helper\Select::languages(), 'JFIELD_LANGUAGE_LABEL')
            </div>
        </div>

        <div class="akeeba-filter-bar akeeba-filter-bar--right">
            <div class="akeeba-filter-element akeeba-form-group">
                <label for="limit" class="element-invisible">
                    @lang('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC')
                </label>
                {{ $this->pagination->getLimitBox() }}
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <label for="directionTable" class="element-invisible">
                    @lang('JFIELD_ORDERING_DESC')
                </label>
                <select name="directionTable" id="directionTable" class="input-medium custom-select" onchange="Joomla.orderTable()">
                    <option value="">
                        @lang('JFIELD_ORDERING_DESC')
                    </option>
                    <option value="asc" <?php echo ($this->order_Dir == 'asc') ? 'selected="selected"' : ""; ?>>
                        @lang('JGLOBAL_ORDER_ASCENDING')
                    </option>
                    <option value="desc" <?php echo ($this->order_Dir == 'desc') ? 'selected="selected"' : ""; ?>>
                        @lang('JGLOBAL_ORDER_DESCENDING')
                    </option>
                </select>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <label for="sortTable" class="element-invisible">
                    @lang('JGLOBAL_SORT_BY')
                </label>
                <select name="sortTable" id="sortTable" class="input-medium custom-select" onchange="Joomla.orderTable()">
                    <option value="">
                        @lang('JGLOBAL_SORT_BY')
                    </option>
                    @jhtml('select.options', $this->sortFields, 'value', 'text', $this->order)
                </select>
            </div>
        </div>

    </section>

    <table class="akeeba-table akeeba-table--striped" id="itemsList">
        <thead>
        <tr>
            <th width="8%">
                @sortgrid('ordering', '<i class="icon-menu-2"></i>')
            </th>
            <th width="32">
                @jhtml('FEFHelper.browse.checkall')
            </th>
            <th>
                @lang('LBL_ITEMS_CATEGORY')
            </th>
            <th>
                @sortgrid('release', 'LBL_ITEMS_RELEASE')
            </th>
            <th>
                @sortgrid('title', 'LBL_ITEMS_TITLE')
            </th>
            <th>
                @sortgrid('type', 'LBL_ITEMS_TYPE')
            </th>
            <th>
                @lang('LBL_ITEMS_ENVIRONMENTS')
            </th>
            <th>
                @sortgrid('access', 'JFIELD_ACCESS_LABEL')
            </th>
            <th width="8%">
                @sortgrid('published', 'JPUBLISHED')
            </th>
            <th>
                @sortgrid('hits', 'JGLOBAL_HITS')
            </th>
            <th>
                @sortgrid('language', 'JFIELD_LANGUAGE_LABEL')
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
        @unless(count($this->items))
            <tr>
                <td colspan="11">
                    @lang('COM_ARS_COMMON_NORECORDS')
                </td>
            </tr>
        @else
	        <?php
	        $i = 0;
	        /** @var \Akeeba\ReleaseSystem\Admin\Model\Items $row */
	        ?>
            @foreach($this->items as $row)
		        <?php
		        /** @var \Akeeba\ReleaseSystem\Admin\Model\Items $row */

		        $category_id = Releases::forceEagerLoad($row->release_id, 'category_id');
		        $canEdit = $user->authorise('core.admin') || $user->authorise('core.edit', 'com_ars.category.' . $category_id);
		        $enabled = $user->authorise('core.edit.state', 'com_ars')
		        ?>
                <tr>
                    <td>
                        @jhtml('FEFHelper.browse.order', 'ordering', $row->ordering)
                    </td>
                    <td>
                        @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
                    </td>
                    <td>
                        {{{ \Akeeba\ReleaseSystem\Site\Model\Categories::forceEagerLoad($category_id, 'title') }}}
                    </td>
                    <td>
                        {{{ \Akeeba\ReleaseSystem\Site\Model\Releases::forceEagerLoad($row->release_id, 'version') }}}
                    </td>
                    <td>
                        <a href="javascript:arsItemsProxy('{{{ $row->id }}}', '{{{ $row->title }}}')">
                            {{{ $row->title }}}
                        </a>
                    </td>
                    <td>
                        @if ($row->type == 'link')
                            @lang('LBL_ITEMS_TYPE_LINK')
                        @else
                            @lang('LBL_ITEMS_TYPE_FILE')
                        @endif
                    </td>
                    <td>
                        @foreach ($row->environments as $environment)
                            <span class="akeeba-label--teal ars-environment-icon">{{ \Akeeba\ReleaseSystem\Admin\Helper\Select::environmentTitle((int)$environment) }}</span>
                        @endforeach
                    </td>
                    <td>
                        {{ \Akeeba\ReleaseSystem\Admin\Helper\Html::accessLevel($row->access) }}
                    </td>
                    <td>
                        @jhtml('FEFHelper.browse.published', $row->published, $i, '', $enabled)
                    </td>
                    <td>
                        {{{ $row->hits }}}
                    </td>
                    <td>
                        {{ \Akeeba\ReleaseSystem\Admin\Helper\Html::language($row->language) }}
                    </td>
                </tr>
            @endforeach
        @endunless
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
$function = $this->input->getCmd('function', 'arsSelectItem');
?>
<script type="text/javascript">
	function arsItemsProxy(id, title)
	{
		if (window.parent) window.parent.<?php echo $this->escape($function); ?>(id, title);
	}
</script>
