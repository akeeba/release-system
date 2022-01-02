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
use Joomla\CMS\Uri\Uri;

/** @var \Akeeba\Component\ARS\Administrator\View\Updatestreams\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$user      = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$nullDate  = Factory::getDbo()->getNullDate();

$i = 0;

?>

<form action="<?= Route::_('index.php?option=com_ars&view=updatestreams'); ?>"
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
							<?= Text::_('COM_ARS_UPDATESTREAMS_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_UPDATESTREAM_FIELD_NAME', 'name', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?= Text::_('COM_ARS_UPDATESTREAM_FIELD_TYPE') ?>
							</th>
							<th scope="col" class="d-none d-md-table-cell">
								<?= Text::_('COM_ARS_UPDATESTREAM_FIELD_ELEMENT') ?>
							</th>
							<th scope="col" class="d-none d-md-table-cell">
								<?= Text::_('COM_ARS_UPDATESTREAM_LBL_LINKS') ?>
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
								|| $user->authorise('core.edit', 'com_ars.category.' . $item->category);
							$canCheckin = $user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId || is_null($item->checked_out);
							$canEditOwn = (
									$user->authorise('core.edit.own', 'com_ars') ||
									$user->authorise('core.edit.own', 'com_ars.category.' . $item->category)
								) && $item->created_by == $userId;
							$canChange  = (
									$user->authorise('core.edit.state', 'com_ars') ||
									$user->authorise('core.edit.state', 'com_ars.category.' . $item->category)
								) && $canCheckin;
							?>
							<tr class="row<?= $i++ % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id, !(empty($item->checked_out_time) || ($item->checked_out_time === $nullDate)), 'cid', 'cb', $item->name); ?>
								</td>

								<td>
									<?php if ($canEdit): ?>
										<a href="<?= Route::_('index.php?option=com_ars&task=updatestream.edit&id=' . (int) $item->id); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= $this->escape($item->name); ?>">
											<?= $this->escape($item->name); ?>
										</a>
									<?php else: ?>
										<?= $this->escape($item->name); ?>
									<?php endif ?>
									<br /> <small> <strong><?= Text::_('COM_ARS_UPDATESTREAM_FIELD_ALIAS') ?></strong>:
										<?= $this->escape($item->alias) ?>
									</small> <br /> <small>
										<strong><?= Text::_('COM_ARS_UPDATESTREAM_CATEGORY_TITLE') ?></strong>:
										<?php if ($canEdit): ?>
											<a href="<?= Route::_('index.php?option=com_ars&task=cattegory.edit&id=' . (int) $item->category); ?>"
											   title="<?= Text::_('JACTION_EDIT'); ?><?= $this->escape($item->cat_title); ?>">
												<?= $this->escape($item->cat_title); ?>
											</a>
										<?php else: ?>
											<?= $this->escape($item->name); ?>
										<?php endif ?>
									</small>
								</td>

								<td>
									<?= Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_' . $item->type) ?>
								</td>

								<td class="d-none d-md-table-cell">
									<?= $this->escape($item->element) ?>
									<br /> <small class="text-muted">
										<strong><?= Text::_('COM_ARS_UPDATESTREAM_FIELD_PACKNAME_ALT') ?></strong>:
										<code><?= $this->escape($item->packname) ?></code> </small>
								</td>

								<td class="d-none d-md-table-cell">
									<a href="<?= Uri::root() . sprintf('index.php?option=com_ars&view=update&format=ini&id=%d', $item->id) ?>"
									   target="_blank"
									> INI </a> • <a
											href="<?= Uri::root() . sprintf('index.php?option=com_ars&view=update&task=stream&format=xml&id=%d', $item->id) ?>"
											target="_blank"
									> XML </a> • <a
											href="<?= Uri::root() . sprintf('index.php?option=com_ars&view=update&task=download&format=raw&id=%d', $item->id) ?>"
											target="_blank"
									> D/L </a>
								</td>

								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'updatestreams.', $user->authorise('core.edit.state', 'com_ars'), 'cb'); ?>
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