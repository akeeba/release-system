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
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ARS\Administrator\View\Logs\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$nullDate  = Factory::getDbo()->getNullDate();


$i = 0;

$catIdFilter       = $this->getModel()->getState('filter.category_id');
$relIdFilter       = $this->getModel()->getState('filter.release_id');
$itemIdFilter      = $this->getModel()->getState('filter.item_id');
$userIdFilter      = $this->getModel()->getState('filter.user_id');
$hasCategoryFilter = !empty($catIdFilter);
$hasReleaseFilter  = !empty($relIdFilter);
$hasItemFilter     = !empty($itemIdFilter);
$hasUserFilter     = !empty($userIdFilter);
$cParams           = ComponentHelper::getParams('com_ars');

if ($hasItemFilter && !$hasReleaseFilter)
{
	$relIdFilter      = $this->getModel()->getReleaseFromItem($itemIdFilter);
	$hasReleaseFilter = !empty($relIdFilter);
}

if ($hasReleaseFilter && !$hasCategoryFilter)
{
	$catIdFilter       = $this->getModel()->getCategoryFromRelease($relIdFilter);
	$hasCategoryFilter = !empty($catIdFilter);
}

$userLayout = new FileLayout('akeeba.ars.common.user', JPATH_ADMINISTRATOR . '/components/com_ars/layout');

?>

<form action="<?= Route::_('index.php?option=com_ars&view=logs'); ?>"
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

							<?php if (!$hasItemFilter || !$hasReleaseFilter || !$hasCategoryFilter): ?>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_ITEM_FIELD_TITLE', 'i.title', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>

							<th scope="col" class="d-none d-md-table-cell">
								<?= Text::_('COM_ARS_LOGS_FIELD_USER') ?>
							</th>

							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_LOGS_FIELD_ACCESSED', 'l.accessed', $listDirn, $listOrder); ?>
							</th>

							<th scope="col">
								<?= Text::_('COM_ARS_LOGS_FIELD_AUTHORIZED') ?>
							</th>

							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'i.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit = $user->authorise('core.edit', 'com_ars')
								|| ($hasCategoryFilter ? $user->authorise('core.edit', 'com_ars.category.' . $catIdFilter) : false);
							?>
							<tr class="row<?= $i++ % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->item_title); ?>
								</td>

								<?php if (!$hasItemFilter || !$hasReleaseFilter || !$hasCategoryFilter): ?>
									<td>
										<?php if (!$hasItemFilter && !$hasReleaseFilter && !$hasCategoryFilter): ?>
											<strong>
												<?= $this->escape($item->item_title) ?>
											</strong>
											<span>
												<?= $this->escape($item->rel_version) ?>
											</span>
											<br />
											<small>
												<?= $this->escape($item->cat_title) ?>
											</small>
										<?php elseif (!$hasItemFilter && !$hasReleaseFilter): ?>
											<strong>
												<?= $this->escape($item->item_title) ?>
											</strong>
											<span>
												<?= $this->escape($item->rel_version) ?>
											</span>
										<?php else: ?>
											<strong>
												<?= $this->escape($item->item_title) ?>
											</strong>
										<?php endif ?>
									</td>
								<?php endif; ?>

								<td class="d-none d-md-table-cell">
									<?= $userLayout->render([
										'user_id'  => $item->user_id,
										'username' => $item->user_username,
										'name'     => $item->user_fullname,
										'email'    => $item->user_email,
										'showLink' => true,
									]) ?>
								</td>

								<td>
									<div class="fw-bold">
										<?= HTMLHelper::_('ars.formatDate', $item->accessed_on) ?>
									</div>
									<div class="text-monospace text-success my-1">
										<?= $this->escape($item->ip) ?>
									</div>
									<div class="d-none d-lg-block"
										 style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:25vw">
										<span title="<?= $this->escape($item->referer) ?>" class="hasTooltip">
											<?= $this->escape($item->referer) ?>
										</span>
									</div>
								</td>

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->authorized, $i, 'logs.', false, 'cb'); ?>
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

				<?php endif; ?>

				<input type="hidden" name="task" value=""> <input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>