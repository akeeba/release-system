<?php
/**
 *  @package  	AkeebaReleaseSystem
 *  @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 *  @license   	GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use FOF30\Utils\FEFHelper\Html as FEFHtml;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Logs\Html */

defined('_JEXEC') or die;

$js = FEFHtml::jsOrderingBackend($this->order);
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

		<?php echo FEFHtml::selectOrderingBackend($this->getPagination(), $this->sortFields, $this->order, $this->order_Dir)?>

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