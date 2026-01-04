<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ARS\Site\View\Dlidlabels\HtmlView $this */

/**
 * HTMLHelper's `behavior.multiselect` is deprecated in Joomla 6.
 *
 * See Joomla PR 45925.
 */
call_user_func(function(string $formName = 'adminForm') {
	if (version_compare(JVERSION, '5.999.999', 'lt'))
	{
		HTMLHelper::_('behavior.multiselect');

		return;
	}

	$doc       = Factory::getApplication()->getDocument();
	$doc->addScriptOptions('js-multiselect', ['formName' => $formName]);
	$doc->getWebAssetManager()->useScript('multiselect');
});

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$app       = Factory::getApplication();
$user      = $app->getIdentity();
$userId    = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$nullDate  = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)->getNullDate();
$Itemid    = $app->getInput()->getInt('Itemid', null);
$token     = $app->getFormToken();

$i = 0;

?>
<form action="<?= Route::_('index.php?option=com_ars&view=dlidlabels'); ?>"
	  method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<div class="alert alert-info mb-5">
					<?= Text::sprintf('COM_ARS_DLIDLABELS_MAINDLID', $this->getModel()->myDownloadID()) ?>
				</div>
				<?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
				<div class="card card-body my-2">
					<div>
						<a href="<?= Route::_(sprintf("index.php?option=com_ars&view=newdlidlabel&Itemid=%s&returnurl=%s", $Itemid, $this->returnURL)) ?>"
						   class="btn btn-success">
							<span class="fa fa-plus-circle"></span>
							<?= Text::_('JGLOBAL_FIELD_ADD') ?>
						</a>
					</div>
				</div>
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
							<td class="text-center visually-hidden" aria-hidden="true">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col">
								<?= HTMLHelper::_('searchtools.sort', 'COM_ARS_DLIDLABELS_FIELD_TITLE', 'title', $listDirn, $listOrder); ?>
							</th>
							<th scope="col">
								<?= Text::_('COM_ARS_DLIDLABELS_FIELD_DLID') ?>
							</th>
							<th scope="col" class="w-1">
								<?= Text::_('JPUBLISHED') ?>
							</th>
							<th scope="col" class="w-1">
								<?= Text::_('COM_ARS_DLIDLABELS_FIELD_RESET') ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $item) : ?>
							<?php
							$canEdit = !$item->primary;
							?>
							<tr class="row<?= $i++ % 2; ?>" data-draggable-group="0">
								<td class="text-center visually-hidden" aria-hidden="true">
									<?= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
								</td>

								<td>
									<?php $title = $item->primary
										? (sprintf('<strong>%s</strong>', Text::_('COM_ARS_DLIDLABELS_FIELD_PRIMARY')))
										: $this->escape($item->title); ?>
									<?php if ($canEdit): ?>
										<a href="<?= Route::_(sprintf("index.php?option=com_ars&view=dlidlabel&task=edit&id=%d&returnurl=%s&Itemid=%d", (int) $item->id, $this->returnURL, $Itemid)); ?>"
										   title="<?= Text::_('JACTION_EDIT'); ?><?= strip_tags($title) ?>">
											<?= $title ?>
										</a>
									<?php else: ?>
										<span class="badge bg-primary">
											<?= $title ?>
										</span>
									<?php endif ?>
								</td>

								<td>
									<button type="button"
											class="btn btn-secondary btn-sm me-2 ars-copy-button"
											data-copy-target="ars-dlid-<?= $item->id ?>"
									>
										<span class="fa fa-copy"></span>
									</button>
									<code id="ars-dlid-<?= $item->id ?>">
										<?php if ($item->primary): ?>
											<?= $this->escape($item->dlid) ?>
										<?php else: ?>
											<?= $this->escape(sprintf('%u:%s', $item->user_id, $item->dlid)) ?>
										<?php endif; ?>
									</code>
								</td>

								<td>
									<?php if ($item->published): ?>
										<a href="<?= Route::_(
											sprintf(
												"index.php?option=com_ars&view=dlidlabels&task=unpublish&cid[]=%s&%s=1",
												$item->id,
												$token
											)
										) ?>"
										   class="btn btn-success btn-sm hasTooltip"
										   title="<?= Text::_('JPUBLISHED') ?>"
										>
											<span class="fa fa-check fa-fw" aria-hidden="true"></span>
											<span class="visually-hidden"><?= Text::_('JPUBLISHED') ?></span>
										</a>
									<?php else: ?>
										<a href="<?= Route::_(
											sprintf(
												"index.php?option=com_ars&view=dlidlabels&task=publish&cid[]=%s&%s=1",
												$item->id,
												$token
											)
										) ?>"
										   class="btn btn-danger btn-sm hasTooltip"
										   title="<?= Text::_('JPUBLISHED') ?>"
										>
											<span class="fa fa-xmark fa-fw" aria-hidden="true"></span>
											<span class="visually-hidden"><?= Text::_('JPUBLISHED') ?></span>
										</a>
									<?php endif; ?>
								</td>

								<td class="d-none d-md-table-cell" style="width: 6em">
									<a href="<?= Route::_(sprintf('index.php?option=com_ars&view=dlidlabels&task=reset&cid[]=%d&%s=1&Itemid=%s&returnurl=%s', $item->id, $token, $Itemid, $this->returnURL)) ?>"
									   class="btn btn-warning btn-sm hasTooltip"
									   title="<?= Text::_('COM_ARS_DLIDLABELS_FIELD_RESET') ?>"
									> <span class="fa fa-sync-alt"></span> </a>

									<a href="<?= Route::_(sprintf('index.php?option=com_ars&view=dlidlabels&task=delete&cid[]=%d&%s=1&Itemid=%s&returnurl=%s', $item->id, $token, $Itemid, $this->returnURL)) ?>"
									   class="btn btn-danger btn-sm hasTooltip ms-2"
									   title="<?= Text::_('COM_ARS_DLIDLABELS_FIELD_TRASH') ?>"
									> <span class="fa fa-trash"></span> </a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // Load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>
				<?php endif; ?>

				<input type="hidden" name="task" value=""> <input type="hidden" name="boxchecked" value="0">
				<input type="hidden" name="boxchecked" value="0">
				<input type="hidden" name="returnurl" value="<?= $this->returnURL ?>">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
