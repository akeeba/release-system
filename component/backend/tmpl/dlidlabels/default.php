<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ARS\Administrator\View\Dlidlabels\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$nullDate  = Factory::getDbo()->getNullDate();

$i = 0;

$userLayout = new FileLayout('akeeba.ars.common.user', JPATH_ADMINISTRATOR . '/components/com_ars/layout');

?>
<form action="<?= Route::_('index.php?option=com_ars&view=dlidlabels'); ?>"
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
							<?= Text::_('COM_ARS_DLIDLABELS_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col">
								<?= Text::_('COM_ARS_DLIDLABELS_FIELD_USER_ID') ?>
							</th>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_DLIDLABELS_FIELD_TITLE', 'title', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-1">
								<?= Text::_('COM_ARS_DLIDLABELS_FIELD_PRIMARY_LABEL') ?>
							</th>
							<th scope="col">
								<?= Text::_('COM_ARS_DLIDLABELS_FIELD_DLID') ?>
							</th>
							<th scope="col">
								<?= Text::_('JPUBLISHED') ?>
							</th>
							<th scope="col" class="w-1 d-none d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit    = $user->authorise('core.edit', 'com_ars')
								|| ($item->user_id == $user->id);
							$canCheckin = $user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId || is_null($item->checked_out);
							$canChange  = (
									$user->authorise('core.edit.state', 'com_ars') ||
									($item->user_id == $user->id)
								) && $canCheckin;
							?>
							<tr class="row<?= $i++ % 2; ?>" data-draggable-group="0">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->title); ?>
								</td>

								<td>
									<?= $userLayout->render([
										'user_id'  => $item->user_id,
										'username' => $item->username,
										'name'     => $item->name,
										'email'    => $item->email,
										'showLink' => true,
									]) ?>
								</td>

								<td>
									<?php $title = $item->primary
										? (sprintf('<strong>%s</strong>', Text::_('COM_ARS_DLIDLABELS_FIELD_PRIMARY')))
										: $this->escape($item->title); ?>
									<?php if ($canEdit): ?>
										<a href="<?= Route::_('index.php?option=com_ars&task=dlidlabel.edit&id=' . (int) $item->id); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= strip_tags($title) ?>">
											<?= $title ?>
										</a>
									<?php else: ?>
										<?= $title ?>
									<?php endif ?>
								</td>

								<td>
									<?= Text::_($item->primary ? 'COM_ARS_DLIDLABELS_FIELD_PRIMARY' : 'COM_ARS_DLIDLABELS_FIELD_ADDON') ?>
								</td>

								<td>
									<code>
										<?php if ($item->primary): ?>
											<?= $this->escape($item->dlid) ?>
										<?php else: ?>
											<?= $this->escape(sprintf('%u:%s', $item->user_id, $item->dlid)) ?>
										<?php endif; ?>
									</code>
								</td>

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'dlidlabels.', $canChange, 'cb'); ?>
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