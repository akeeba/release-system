<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
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

/** @var \Akeeba\Component\ARS\Administrator\View\Releases\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'r.ordering';
$nullDate  = Factory::getDbo()->getNullDate();

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_ars&task=releases.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$i = 0;

$hasCategoryFilter = !empty($this->getModel()->getState('filter.category_id'));
$cParams = ComponentHelper::getParams('com_ars');
?>

<form action="<?= Route::_('index.php?option=com_ars&view=releases'); ?>"
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
							<?= Text::_('COM_ARS_RELEASES_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'r.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_RELEASES_FIELD_VERSION', 'r.version', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="d-none d-md-table-cell">
								<?= Text::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_RELEASES_FIELD_RELEASED', 'r.created', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JFIELD_ACCESS_LABEL', 'r.access', $listDirn, $listOrder); ?>
							</th>
							<?php if (Multilanguage::isEnabled()) : ?>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'JFIELD_LANGUAGE_LABEL', 'r.language', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<th scope="col">
								<?= Text::_('JPUBLISHED') ?>
							</th>
							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'r.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody <?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit    = $user->authorise('core.edit', 'com_ars')
								|| $user->authorise('core.edit', 'com_ars.category.' . $item->category_id);
							$canCheckin = $user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId || is_null($item->checked_out);
							$canEditOwn = (
									$user->authorise('core.edit.own', 'com_ars') ||
									$user->authorise('core.edit.own', 'com_ars.category.' . $item->category_id)
								) && $item->created_by == $userId;
							$canChange  = (
									$user->authorise('core.edit.state', 'com_ars') ||
									$user->authorise('core.edit.state', 'com_ars.category.' . $item->category_id)
								) && $canCheckin;
							?>
							<tr class="row<?= $i++ % 2; ?>" data-draggable-group="<?= $item->category_id ?>>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->version); ?>
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
										<a href="<?= Route::_('index.php?option=com_ars&task=release.edit&id=' . (int) $item->id); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= $this->escape($item->version); ?>">
											<?= $this->escape($item->version); ?>
										</a>
									<?php else: ?>
										<?= $this->escape($item->version); ?>
									<?php endif ?>
									<br/>
									<small>
										<strong><?= Text::_('JALIAS') ?></strong>:
										<?= $this->escape($item->alias) ?>
									</small>
									<?php if (!$hasCategoryFilter): ?>
										<br />
										<small> <strong><?= Text::_('COM_ARS_RELEASES_FIELD_CATEGORY') ?></strong>:
											<?php if ($canEdit): ?>
												<a href="<?= Route::_('index.php?option=com_ars&task=category.edit&id=' . $item->category_id) ?>">
													<?= $this->escape($item->cat_title) ?>
												</a>
											<?php else: ?>
												<?= $this->escape($item->cat_title) ?>
											<?php endif; ?>
										</small>
									<?php endif; ?>
								</td>

								<td class="d-none d-md-table-cell">
									<?= Text::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>
								</td>

								<td>
									<?= HTMLHelper::_('ars.formatDate', $item->created) ?>
								</td>

								<td class="d-none d-md-table-cell">
									<?= $this->escape($item->access_level) ?>
								</td>

								<?php if (Multilanguage::isEnabled()) : ?>
									<td>
										<?= LayoutHelper::render('joomla.content.language', $item); ?>
									</td>
								<?php endif; ?>

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'releases.', $user->authorise('core.edit.state', 'com_ars'), 'cb'); ?>
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
								'title'  => Text::_('COM_ARS_RELEASES_BATCH_OPTIONS'),
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