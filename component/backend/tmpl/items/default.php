<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \Akeeba\Component\ARS\Administrator\View\Items\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'i.ordering';
$nullDate  = Factory::getDbo()->getNullDate();

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_ars&task=items.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$i = 0;

$catIdFilter       = $this->getModel()->getState('filter.category_id');
$relIdFilter       = $this->getModel()->getState('filter.release_id');
$hasCategoryFilter = !empty($catIdFilter);
$hasReleaseFilter  = !empty($relIdFilter);
$cParams           = ComponentHelper::getParams('com_ars');

if ($hasReleaseFilter && !$hasCategoryFilter)
{
	$catIdFilter       = $this->getModel()->getCategoryFromRelease($relIdFilter);
	$hasCategoryFilter = !empty($catIdFilter);
}

$modal = isset($modal) ? boolval($modal) : false;

$actionUrl = 'index.php?option=com_ars&view=items';

if (Factory::getApplication()->isClient('site'))
{
	$actionUrl = Uri::root() . $actionUrl . '&layout=modal&tmpl=component';
}
else
{
	$actionUrl = Route::_($actionUrl . ($modal ? '&layout=modal&tmpl=component' : ''));
}

?>

<form action="<?= $actionUrl; ?>"
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
							<?= Text::_('COM_ARS_ITEMS_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'i.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_ITEM_FIELD_TITLE', 'i.title', $listDirn, $listOrder); ?>
							</th>
							<?php if (!$hasCategoryFilter || !$hasReleaseFilter): ?>
								<th scope="col">
									<?= Text::_('COM_ARS_ITEM_FIELD_RELEASE'); ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JFIELD_ACCESS_LABEL', 'i.access', $listDirn, $listOrder); ?>
							</th>
							<?php if (Multilanguage::isEnabled()) : ?>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'JFIELD_LANGUAGE_LABEL', 'i.language', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<th scope="col">
								<?= Text::_('JPUBLISHED') ?>
							</th>
							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'i.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody <?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit    = $user->authorise('core.edit', 'com_ars')
								|| ($hasCategoryFilter ? $user->authorise('core.edit', 'com_ars.category.' . $catIdFilter) : false);
							$canCheckin = $user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId || is_null($item->checked_out);

							$canEditOwn = (
									$user->authorise('core.edit.own', 'com_ars') ||
									($hasCategoryFilter ? $user->authorise('core.edit.own', 'com_ars.category.' . $catIdFilter) : false)
								) && $item->created_by == $userId;

							$canChange = (
									$user->authorise('core.edit.state', 'com_ars') ||
									($hasCategoryFilter ? $user->authorise('core.edit.state', 'com_ars.category.' . $catIdFilter) : false)
								) && $canCheckin;
							?>
							<tr class="row<?= $i++ % 2; ?>" data-draggable-group="<?= $item->release_id ?>>">
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
									<?php if ($modal): ?>
										<a class="hasArsItemProxy"
										   data-arsrowid="<?= $item->id ?>"
										   data-arstitle="<?= $this->escape($item->title); ?>"
										>
											<?= $this->escape($item->title); ?>
										</a>
									<?php elseif ($canEdit): ?>
										<a href="<?= Route::_('index.php?option=com_ars&task=item.edit&id=' . (int) $item->id); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= $this->escape($item->title); ?>">
											<?= $this->escape($item->title); ?>
										</a>
									<?php else: ?>
										<?= $this->escape($item->title); ?>
									<?php endif ?>
									<br /> <small> <strong><?= Text::_('JALIAS') ?></strong>:
										<?= $this->escape($item->alias) ?>
									</small>
								</td>

								<?php if (!$hasCategoryFilter || !$hasReleaseFilter): ?>
									<td>
										<?php if (!$hasCategoryFilter): ?>
											<?php if ($canEdit && !$modal): ?>
												<a href="<?= Route::_('index.php?option=com_ars&task=category.edit&id=' . $item->cat_id) ?>">
													<?= $this->escape($item->cat_title) ?>
												</a>
											<?php else: ?>
												<?= $this->escape($item->cat_title) ?>
											<?php endif; ?>
											<br />
											<span title="<?= Text::_('COM_ARS_ITEM_FIELD_RELEASE') ?>">
											<?php if ($canEdit && !$modal): ?>
												<a href="<?= Route::_('index.php?option=com_ars&task=release.edit&id=' . $item->release_id) ?>">
													<?= $this->escape($item->version) ?>
												</a>
											<?php else: ?>
												<?= $this->escape($item->version) ?>
											<?php endif; ?>
										</span>
										<?php else: ?>
											<span title="<?= Text::_('COM_ARS_ITEM_FIELD_RELEASE') ?>">
											<?php if ($canEdit && !$modal): ?>
												<a href="<?= Route::_('index.php?option=com_ars&task=release.edit&id=' . $item->release_id) ?>">
													<?= $this->escape($item->version) ?>
												</a>
											<?php else: ?>
												<?= $this->escape($item->version) ?>
											<?php endif; ?>
										</span>
										<?php endif; ?>
									</td>
								<?php endif; ?>

								<td class="d-none d-md-table-cell">
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
								'title'  => Text::_('COM_ARS_ITEMS_BATCH_OPTIONS'),
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