<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Site\View\Releases\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @var HtmlView $this
 * @var object   $item
 */

$release_url = $this->getReleaseUrl($item);

switch ($item->maturity)
{
	case 'stable':
		$maturityClass = 'bg-success';
		break;

	case 'rc':
		$maturityClass = 'bg-info';
		break;

	case 'beta':
		$maturityClass = 'bg-warning';
		break;

	case 'alpha':
		$maturityClass = 'bg-danger';
		break;

	default:
		$maturityClass = 'bg-dark';
		break;
}

$environments = ($this->params->get('show_environments', 1) == 1) ? $this->getModel()->getEnvironments($item) : [];

HTMLHelper::_('bootstrap.collapse', '.ars-collapse');
?>

<div class="ars-release-<?= $item->id ?> mb-3 pb-3 border-bottom">
	<h4 class="h3 d-flex m-0">
		<a href="<?= $release_url ?>" class="pe-3">
			<?= Text::_('COM_ARS_RELEASES_VERSION') ?>
			<?= $this->escape($item->version) ?>
		</a>
		<span class="badge <?= $maturityClass ?> fs-5">
			<?= Text::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>
		</span>
	</h4>
	<?php if (!empty($environments)): ?>
		<p>
			<?php foreach ($environments as $environment): ?>
				<span class="badge bg-light text-dark ars-environment-icon"><?= $environment ?></span>
			<?php endforeach; ?>
		</p>
	<?php endif ?>
	<div class="d-flex">
		<div class="flex-grow-1">
			<strong><?= Text::_('COM_ARS_RELEASE_LBL_RELEASEDON') ?></strong>:
			<?= HTMLHelper::_('ars.formatDate', $item->created, true, Text::_('DATE_FORMAT_LC1')) ?>
		</div>
		<div>
			<button class="btn btn-dark btn-sm release-info-toggler" type="button"
					data-bs-toggle="collapse"
					data-bs-target="#ars-release-<?= $item->id ?>-info"
					aria-expanded="false"
					aria-controls="ars-release-<?= $item->id ?>-info"
			>
				<span class="fa fa-info-circle"></span>
				<?= Text::_('COM_ARS_RELEASES_MOREINFO') ?>
			</button>
		</div>
	</div>

	<div id="ars-release-<?= $item->id ?>-info" class="collapse">
		<div class="card card-body mt-2">
			<table class="ars-release-properties table table-striped mb-2">
				<tr>
					<td>
						<?= Text::_('COM_ARS_RELEASES_FIELD_MATURITY') ?>
					</td>
					<td>
						<?= Text::_('COM_ARS_RELEASES_MATURITY_' . $item->maturity) ?>

					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_ARS_RELEASE_LBL_RELEASEDON') ?>
					</td>
					<td>
						<?= HTMLHelper::_('ars.formatDate', $item->created, true, Text::_('DATE_FORMAT_LC1')) ?>
					</td>
				</tr>
				<?php if ($this->params->get('show_downloads', 1)): ?>
					<tr>
						<td><?= Text::_('COM_ARS_RELEASE_LBL_HITS') ?></td>
						<td>
							<?= Text::plural('COM_ARS_RELEASE_LBL_TIME', $item->hits) ?>
						</td>
					</tr>
				<?php endif ?>
			</table>

			<div id="ars-release-<?= $item->id ?>-notes">
				<h3>
					<?= Text::_('COM_ARS_RELEASE_NOTES_LABEL') ?>
				</h3>
				<?= HTMLHelper::_('ars.preProcessMessage', $item->notes, 'com_ars.release_notes') ?>
			</div>

			<?php if (!($no_link ?? false)): ?>
				<p class="mt-2">
					<a href="<?= $release_url ?>" class="btn btn-primary">
						<?= Text::_('COM_ARS_RELEASE_LBL_VIEW_ITEMS') ?>
					</a>
				</p>
			<?php endif ?>
		</div>
	</div>
</div>
