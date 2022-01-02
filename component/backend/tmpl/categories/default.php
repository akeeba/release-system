<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \Akeeba\Component\ARS\Administrator\View\Categories\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'ordering';
$nullDate  = Factory::getDbo()->getNullDate();

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_ars&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$i = 0;

?>

<form action="<?= Route::_('index.php?option=com_ars&view=categories'); ?>"
	  method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span
								class="visually-hidden"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="articleList">
						<caption class="visually-hidden">
							<?= Text::_('COM_ARS_CATEGORIES_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_CATEGORIES_FIELD_TITLE', 'title', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_CATEGORIES_FIELD_TYPE', 'type', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'JFIELD_ACCESS_LABEL', 'access', $listDirn, $listOrder); ?>
							</th>
							<?php if (Multilanguage::isEnabled()) : ?>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'JFIELD_LANGUAGE_LABEL', 'language', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<th scope="col">
								<?= Text::_('JPUBLISHED') ?>
							</th>
							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody <?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit    = $user->authorise('core.edit', 'com_ars')
								|| $user->authorise('core.edit', 'com_ars.category.' . $item->id);
							$canCheckin = $user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId || is_null($item->checked_out);
							$canEditOwn = (
									$user->authorise('core.edit.own', 'com_ars') ||
									$user->authorise('core.edit.own', 'com_ars.category.' . $item->id)
								) && $item->created_by == $userId;
							$canChange  = (
									$user->authorise('core.edit.state', 'com_ars') ||
									$user->authorise('core.edit.state', 'com_ars.category.' . $item->id)
								) && $canCheckin;
							?>
							<tr class="row<?= $i++ % 2; ?>" data-draggable-group="0">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->title); ?>
								</td>

								<td class="text-center d-none d-md-table-cell">
									<?php
									$iconClass = '';

									if (!$canChange)
									{
										$iconClass = ' inactive';
									}
									elseif (!$saveOrder)
									{
										$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
									}
									?>
									<span class="sortable-handler<?php echo $iconClass ?>">
										<span class="icon-ellipsis-v" aria-hidden="true"></span>
									</span>
									<?php if ($canChange && $saveOrder) : ?>
										<input type="text" name="order[]" size="5"
											   value="<?php echo $item->ordering; ?>"
											   class="width-20 text-area-order hidden">
									<?php endif; ?>
								</td>
								<td>
									<?php if ($canEdit): ?>
										<a href="<?= Route::_('index.php?option=com_ars&task=category.edit&id=' . (int) $item->id); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= $this->escape($item->title); ?>">
											<?= $this->escape($item->title); ?>
										</a>
									<?php else: ?>
										<?= $this->escape($item->title); ?>
									<?php endif ?>
								</td>

								<td>
									<?= Text::_('COM_ARS_CATEGORIES_TYPE_' . $item->type) ?>
								</td>

								<td>
									<?= $this->escape($item->access_level) ?>
								</td>

								<?php if (Multilanguage::isEnabled()) : ?>
									<td>
										<?= LayoutHelper::render('joomla.content.language', $item); ?>
									</td>
								<?php endif; ?>

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'categories.', $user->authorise('core.edit.state', 'com_ars'), 'cb'); ?>
								</td>

								<td class="w-1 d-none d-md-table-cell">
									<?= $item->id ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // Load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>

					<?php // Load the batch processing form. ?>
					<?php if ($user->authorise('core.create', 'com_ars')
						&& $user->authorise('core.edit', 'com_ars')
						&& $user->authorise('core.edit.state', 'com_ars')) : ?>
						<?php echo HTMLHelper::_(
							'bootstrap.renderModal',
							'collapseModal',
							[
								'title'  => Text::_('COM_ARS_CATEGORIES_BATCH_OPTIONS'),
								'footer' => $this->loadTemplate('batch_footer'),
							],
							$this->loadTemplate('batch_body')
						); ?>
					<?php endif; ?>
				<?php endif; ?>

				<input type="hidden" name="task" value=""> <input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>