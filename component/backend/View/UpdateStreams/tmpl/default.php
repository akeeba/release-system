<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use FOF30\Utils\FEFHelper\Html as FEFHtml;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

$js = FEFHtml::jsOrderingBackend($this->order);
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
                       id="filter_name" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['name']); ?>"
                       title="<?php echo \JText::_('LBL_UPDATES_NAME'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <input type="text" name="element" placeholder="<?php echo \JText::_('LBL_UPDATES_ELEMENT'); ?>"
                       id="filter_element" onchange="document.adminForm.submit()"
                       value="<?php echo $this->escape($this->filters['element']); ?>"
                       title="<?php echo \JText::_('LBL_UPDATES_ELEMENT'); ?>"/>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <?php echo Select::updateTypes('type', $this->filters['type'], ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
				<?php echo Select::categories($this->filters['category'], 'category', ['onchange' => 'document.adminForm.submit()'])?>
            </div>

            <div class="akeeba-filter-element akeeba-form-group">
                <?php echo Select::published($this->filters['published'], 'published', ['onchange' => 'document.adminForm.submit()'])?>
            </div>
        </div>

		<?php echo FEFHtml::selectOrderingBackend($this->getPagination(), $this->sortFields, $this->order, $this->order_Dir)?>

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